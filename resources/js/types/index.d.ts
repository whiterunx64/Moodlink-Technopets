export interface User {
    id: number;
    name: string;
    email: string;
    email_verified_at?: string;
}

export type PageProps<
    T extends Record<string, unknown> = Record<string, unknown>,
> = T & {
    auth: {
        user: User;
    };
    flash: {
        error?: string;
    };
};

export type Mood = 'Drianed' | 'Stressed' | 'Content' | 'Excited';
export type PostStatus = 'flagged' | 'safe';
export type PostFilter = 'All' | 'Flagged' | 'Safe';

export interface FormErrors {
    email?: string;
    password?: string;
    [key: string]: string | undefined;
}
export interface PostFilters {
    status:  string | null;
    section: string | null;
    mood:    string | null;
}

export interface Post {
    id: number;
    anonymous_name: string | null;
    section: string;
    time: string;
    date: string;
    mood: string;
    status: 'flagged' | 'safe';
    content: string | null;
}

export interface AdminProfile {
    firstName: string;
    lastName: string;
    email: string;
    phone: string;
    role: string;
    department: string;
}

export interface PasswordForm {
    current: string;
    newPass: string;
    confirm: string;
}

export interface NotificationPreferences {
    newFlags: boolean;
    appointments: boolean;
    escalations: boolean;
    weeklyReports: boolean;
    systemUpdates: boolean;
}
