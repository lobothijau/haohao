export type ReviewAccuracy = {
    all_time: number;
    last_7_days: number;
};

export type DayActivity = {
    date: string;
    count: number;
};

export type HskLevelProgress = {
    level: number;
    learned: number;
    total: number;
};
