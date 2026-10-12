@props(['shortcuts'])

<div
    class="shortcut-help"
    x-data
    x-on:keydown.window.capture="
        if ($refs.shortcutDialog.open) {
            if ($event.key === 'Escape') $refs.shortcutDialog.close();
            $event.stopImmediatePropagation();
        } else if ($event.key === '?') {
            $refs.shortcutDialog.showModal();
        }
    "
>
    <button class="btn shortcut-help-trigger" type="button" aria-haspopup="dialog" @click="$refs.shortcutDialog.showModal()">
        Tastaturhilfe <kbd>?</kbd>
    </button>

    <dialog
        class="shortcut-dialog"
        x-ref="shortcutDialog"
        aria-labelledby="shortcut-dialog-heading"
    >
        <div class="shortcut-dialog-content">
            <h2 id="shortcut-dialog-heading">Tastaturkürzel</h2>
            <ul>
                @foreach ($shortcuts as $shortcut => $description)
                    <li><kbd>{{ $shortcut }}</kbd> {{ $description }}</li>
                @endforeach
                <li><kbd>?</kbd> Hilfe öffnen</li>
                <li><kbd>Esc</kbd> Hilfe schließen</li>
            </ul>
            <button class="btn btn--primary" type="button" autofocus @click="$refs.shortcutDialog.close()">
                Schließen
            </button>
        </div>
    </dialog>
</div>
