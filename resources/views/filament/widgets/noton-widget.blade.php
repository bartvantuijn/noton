<x-filament-widgets::widget>
    <x-filament::section>
        <div
            x-data="{
                refreshTimer: null,
                startTimer() {
                    this.refreshTimer = setInterval(() => $wire.$refresh(), 10000);
                },
                resetTimer() {
                    clearInterval(this.refreshTimer);
                    this.startTimer();
                }
            }"
            x-init="startTimer()"
            class="flex items-center text-center"
        >
            <div class="flex-1">
                <button
                    type="button"
                    @click="resetTimer(); $wire.$refresh()">
                    <p class="text-sm text-gray-500 dark:text-gray-400">
                        {!! $quote !!}
                    </p>
                </button>
            </div>
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
