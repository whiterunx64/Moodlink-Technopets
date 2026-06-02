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
};

export type Mood = 'happy' | 'sad' | 'anxious' | 'neutral' | 'drained' | 'stressed';
export type PostStatus = 'flagged' | 'safe';
export type PostFilter = 'All' | 'Flagged' | 'Safe';

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
