@php
    use Filament\Notifications\Notification;
    use Filament\Support\Icons\Heroicon;
    use App\Models\Setting;
    use Illuminate\Support\Arr;

    $notice = Setting::singleton()->get('notice', []);
    $enabled = (bool) Arr::get($notice, 'enabled', false);
    $title = (string) Arr::get($notice, 'title', '');
    $message = (string) Arr::get($notice, 'message', '');
    $style = (string) Arr::get($notice, 'style', 'primary');

    $icon = match ($style) {
        'success' => Heroicon::OutlinedCheckCircle,
        'warning' => Heroicon::OutlinedExclamationTriangle,
        'danger' => Heroicon::OutlinedXCircle,
        default => Heroicon::OutlinedInformationCircle,
    };

    $color = match ($style) {
        'success' => 'success',
        'warning' => 'warning',
        'danger' => 'danger',
        default => 'primary',
    };

    $storageKey = 'notice';
    $storageValue = json_encode([$title, $message, $style]);

    $notification = Notification::make('notice')
        ->title(filled($title) ? $title : __('Notice'))
        ->body($message)
        ->icon($icon)
        ->iconColor($color)
        ->persistent();
@endphp

@if ($enabled && (filled($title) || filled($message)))
    <div
        x-data
        x-init="
            if (localStorage.getItem(@js($storageKey)) === @js($storageValue)) {
                $el.remove();
            }
        "
        x-on:notification-closed.window.camel="
            if ($event.detail.id !== 'notice') {
                return;
            }

            localStorage.setItem(@js($storageKey), @js($storageValue));
            $el.remove();
        "
        class="px-4 pt-4 [&_.fi-no-notification]:max-w-none [&_.fi-no-notification]:w-full"
    >
        {!! $notification->toEmbeddedHtml() !!}
    </div>
@endif
