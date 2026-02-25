export * from './auth';
export * from './blog';
export * from './membership';
export * from './navigation';
export * from './story';
export * from './ui';

import type { Auth } from './auth';

export type FounderCounter = {
    claimed: number;
    limit: number;
};

export type AppPageProps<
    T extends Record<string, unknown> = Record<string, unknown>,
> = T & {
    name: string;
    auth: Auth;
    sidebarOpen: boolean;
    founderCounter: FounderCounter;
    flash?: {
        success?: string;
        error?: string;
    };
    [key: string]: unknown;
};
