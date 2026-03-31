export interface Feature {
    name: string;
    display_name: string;
    description: string;
    is_active: boolean;
    strategy: 'inactive' | 'percentage' | 'users' | 'all';
    strategy_label: string;
    percentage: number;
    user_ids?: string[];
}

export interface FeatureCollection {
    data: Feature[];
}
