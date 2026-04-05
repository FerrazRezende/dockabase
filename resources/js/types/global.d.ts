/// Global type definitions for Inertia shared props
export interface PageProps {
    auth: {
        user: {
            id: string;
            name: string;
            email: string;
            is_admin: boolean;
            avatar?: string;
        };
    };
    activeFeatures?: string[];
    translations?: Record<string, string>;
    locale?: string;
}
