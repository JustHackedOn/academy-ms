<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Setting;
use App\Models\User;
use App\Services\WhatsAppLinkBuilder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class SettingController extends Controller
{
    /**
     * Settings the UI exposes (key => [label, type, group]).
     */
    public const FIELDS = [
        'academy_name' => ['label' => 'Academy Name', 'type' => 'text', 'group' => 'Academy'],
        'academy_phone' => ['label' => 'Contact Phone', 'type' => 'text', 'group' => 'Academy'],
        'academy_email' => ['label' => 'Contact Email', 'type' => 'text', 'group' => 'Academy'],
        'academy_address' => ['label' => 'Address', 'type' => 'textarea', 'group' => 'Academy'],
        'currency' => ['label' => 'Currency Code', 'type' => 'text', 'group' => 'Finance'],
        'receipt_footer' => ['label' => 'Receipt Footer Note', 'type' => 'textarea', 'group' => 'Finance'],
        'whatsapp_template' => ['label' => 'WhatsApp Template (English)', 'type' => 'textarea', 'group' => 'Templates'],
        'whatsapp_template_roman' => ['label' => 'WhatsApp Template (Roman Urdu)', 'type' => 'textarea', 'group' => 'Templates'],
    ];

    public function index(): View
    {
        $this->ensureAdmin();

        $settings = collect(self::FIELDS)->mapWithKeys(
            fn ($meta, $key) => [$key => Setting::get($key, '')]
        )->all();

        // Fill empty template boxes with the shipped defaults so they are editable.
        $settings['whatsapp_template'] = $settings['whatsapp_template'] ?: WhatsAppLinkBuilder::DEFAULT_TEMPLATE_EN;
        $settings['whatsapp_template_roman'] = $settings['whatsapp_template_roman'] ?: WhatsAppLinkBuilder::DEFAULT_TEMPLATE_URDU;

        return view('settings.index', [
            'settings' => $settings,
            'fields' => self::FIELDS,
            'users' => User::orderBy('name')->get(),
            'logo' => Setting::get('academy_logo'),
            'backups' => $this->recentBackups(),
        ]);
    }

    /**
     * Persist academy info, templates and an optional logo upload.
     */
    public function update(Request $request): RedirectResponse
    {
        $this->ensureAdmin();

        $data = $request->validate([
            'academy_name' => ['required', 'string', 'max:120'],
            'academy_phone' => ['nullable', 'string', 'max:40'],
            'academy_email' => ['nullable', 'email', 'max:120'],
            'academy_address' => ['nullable', 'string', 'max:300'],
            'currency' => ['required', 'string', 'max:10'],
            'receipt_footer' => ['nullable', 'string', 'max:300'],
            'whatsapp_template' => ['required', 'string', 'max:1000'],
            'whatsapp_template_roman' => ['required', 'string', 'max:1000'],
            'logo' => ['nullable', 'image', 'mimes:png,jpg,jpeg,webp', 'max:2048'],
        ]);

        foreach (array_keys(self::FIELDS) as $key) {
            Setting::set($key, (string) ($data[$key] ?? ''));
        }

        if ($request->hasFile('logo')) {
            $old = Setting::get('academy_logo');
            if ($old && Storage::disk('public')->exists($old)) {
                Storage::disk('public')->delete($old);
            }

            Setting::set('academy_logo', $request->file('logo')->store('branding', 'public'));
        }

        ActivityLog::log('updated_settings', null, null, ['keys' => array_keys(self::FIELDS)]);

        return redirect()->route('settings.index')->with('success', 'Settings saved successfully.');
    }

    /**
     * Create an additional portal user (admin only).
     */
    public function storeUser(Request $request): RedirectResponse
    {
        $this->ensureAdmin();

        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:120', Rule::unique('users', 'email')],
            'password' => ['required', 'string', Password::min(8)->letters()->numbers(), 'max:120'],
            'role' => ['required', Rule::in(['admin', 'partner'])],
        ]);

        $user = User::create($data);

        ActivityLog::log('created_user', $user, null, ['name' => $user->name, 'role' => $user->role]);

        return redirect()->route('settings.index')->with('success', "User '{$user->name}' created.");
    }

    /**
     * Remove a portal user (admin only, never yourself or the last admin).
     */
    public function destroyUser(User $user): RedirectResponse
    {
        $this->ensureAdmin();

        if ($user->id === auth()->id()) {
            return back()->with('error', 'You cannot delete your own account.');
        }

        if ($user->isAdmin() && User::where('role', 'admin')->count() <= 1) {
            return back()->with('error', 'At least one administrator must remain.');
        }

        $name = $user->name;
        ActivityLog::log('deleted_user', $user, ['name' => $name, 'email' => $user->email], null);

        $user->delete();

        return redirect()->route('settings.index')->with('success', "User '{$name}' removed.");
    }

    /**
     * Newest database backups, if the backup command has ever run.
     */
    protected function recentBackups(): array
    {
        $disk = Storage::disk('local');

        if (! $disk->exists('backups')) {
            return [];
        }

        return collect($disk->files('backups'))
            ->sortDesc()
            ->take(5)
            ->map(fn ($path) => [
                'name' => basename($path),
                'size' => $disk->size($path),
                'modified' => date('d M Y H:i', $disk->lastModified($path)),
            ])
            ->values()
            ->all();
    }

    protected function ensureAdmin(): void
    {
        abort_unless(auth()->user()?->isAdmin(), 403, 'Only administrators can manage portal users.');
    }
}
