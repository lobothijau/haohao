<x-filament-widgets::widget>
    <x-filament::section heading="Retention Cohorts (Weekly)">
        <div class="overflow-x-auto -mx-6">
            <table class="w-full text-sm fi-ta-table divide-y divide-gray-200 dark:divide-white/5">
                <thead class="bg-gray-50 dark:bg-white/5">
                    <tr>
                        <th class="px-4 py-2.5 text-start text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Cohort</th>
                        <th class="px-4 py-2.5 text-end text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Users</th>
                        <th class="px-4 py-2.5 text-center text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Day 1</th>
                        <th class="px-4 py-2.5 text-center text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Day 7</th>
                        <th class="px-4 py-2.5 text-center text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Day 30</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-white/5">
                    @foreach ($this->getCohorts() as $cohort)
                        <tr>
                            <td class="px-4 py-2.5 text-start font-medium text-gray-950 dark:text-white whitespace-nowrap">
                                {{ $cohort['cohort'] }}
                            </td>
                            <td class="px-4 py-2.5 text-end text-gray-500 dark:text-gray-400 tabular-nums">
                                {{ number_format($cohort['users']) }}
                            </td>
                            @foreach (['d1', 'd7', 'd30'] as $period)
                                <td class="px-4 py-2.5 text-center">
                                    @if ($cohort[$period] > 0)
                                        @php
                                            $value = $cohort[$period];
                                            $intensity = min($value / 100, 1);

                                            if ($intensity >= 0.5) {
                                                $bgClass = 'bg-emerald-100 dark:bg-emerald-500/20 text-emerald-700 dark:text-emerald-400';
                                            } elseif ($intensity >= 0.25) {
                                                $bgClass = 'bg-emerald-50 dark:bg-emerald-500/10 text-emerald-600 dark:text-emerald-400';
                                            } else {
                                                $bgClass = 'bg-gray-50 dark:bg-white/5 text-gray-600 dark:text-gray-400';
                                            }
                                        @endphp
                                        <span class="inline-flex items-center justify-center rounded-md px-2 py-1 text-xs font-semibold tabular-nums min-w-[3.5rem] {{ $bgClass }}">
                                            {{ $value }}%
                                        </span>
                                    @else
                                        <span class="text-gray-300 dark:text-gray-600">&mdash;</span>
                                    @endif
                                </td>
                            @endforeach
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        @if (empty($this->getCohorts()) || collect($this->getCohorts())->every(fn ($c) => $c['users'] === 0))
            <div class="py-6 text-center text-sm text-gray-500 dark:text-gray-400">
                No cohort data available yet.
            </div>
        @endif
    </x-filament::section>
</x-filament-widgets::widget>
