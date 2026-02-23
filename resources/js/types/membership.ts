export type Plan = {
    id: number;
    slug: string;
    label: string;
    price: number;
    duration_months: number;
    is_active: boolean;
    sort_order: number;
};

export type Subscription = {
    id: number;
    plan_id: number;
    plan?: Plan;
    status: string;
    midtrans_order_id: string | null;
    midtrans_transaction_id: string | null;
    amount: number;
    starts_at: string | null;
    expires_at: string | null;
};
