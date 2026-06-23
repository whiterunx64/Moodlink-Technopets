export interface User {
    id: number;
    name: string;
    email: string;
    avatar?: string | null;
    email_verified_at?: string;
}

export type PageProps<
    T extends Record<string, unknown> = Record<string, unknown>,
> = T & {
    auth: {
        user: User;
    };
    assets: {
        logo: string;
    };
    flash: {
        error?: string;
        success?: string;
        student_crendetials?: { email: string; password: string } | null;
    };
};

export type Mood = 'Drained' | 'Stressed' | 'Content' | 'Excited';
export type PostStatus = 'flagged' | 'safe';
export type PostFilter = 'All' | 'Flagged' | 'Safe';

export interface FormErrors {
    email?: string;
    password?: string;
    [key: string]: string | undefined;
}
export interface PostFilters {
    status: string | null;
    section: string | null;
    mood: string | null;
    sort: string | null;
}

export interface Post {
    id: number;
    anonymous_name: string | null;
    last_name: string;
    first_name: string;
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
    phone: string;
    role: string;
    department: string;
}

export interface AdminSummary {
    firstName: string;
    lastName: string;
    phone: string | null;
    avatar: string | null;
    role: string;
    status: string;
}

// ── Auth ───────────────────────────────────────────────────────────────────────

export interface LoginPageProps {
    canResetPassword?: boolean;
    status?: string;
}

export interface ProfileSettingsPageProps {
    admin: AdminSummary | null;
    notifications?: Partial<NotificationPreferences>;
    status?: string;
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

export type VerificationStatus = 'pending' | 'verified' | 'unverified';
export type AccountStatus = 'active' | 'suspended';
export type StudentTab = 'All' | 'Pending' | 'Verified' | 'Suspended';

export interface Student {
    id: number;
    auth_user_id: string | null;
    student_id: string;
    name: string;
    first_name: string;
    last_name: string;
    personal_email: string | null;
    contact_number: string | null;
    year_level: string;
    section: string;
    verification_status: VerificationStatus;
    account_status: AccountStatus;
}

export interface StudentAccountFilters {
    search: string | null;
    year_level: number | null;
    tab: string;
}

export interface Paginated<T> {
    data: T[];
    current_page: number;
    last_page: number;
    from: number | null;
    to: number | null;
    total: number;
    per_page: number;
}

// ── Summary Reports ───────────────────────────────────────────────────────────

export type SummaryPeriod = 'this_week' | 'this_month' | 'all_time';

export interface SummaryFilters {
    period: SummaryPeriod;
    tab: string;
}

export interface MoodDistributionItem {
    label: string;
    count: number;
    pct: number;
}

export interface SummaryOverview {
    totalMoodLogs: number;
    avgDailyLogs: number;
    atRiskStudents: number;
    appointmentsSet: number;
    distribution: MoodDistributionItem[];
}

export interface SectionSummary {
    section: string;
    total: number;
    excited: number;
    content: number;
    stressed: number;
    drained: number;
    atRisk: number;
}

export interface AtRiskStudent {
    id: number;
    name: string;
    studentNumber: string;
    section: string;
    moods: string[];
    daysAtRisk: number;
    lastLog: string;
    hasConsultation: boolean;
}

export interface SectionStudentRow {
    id: number;
    name: string;
    initials: string;
    studentNumber: string;
    yearLevel: string;
    trend: 'Declining' | 'Stable' | 'Improving';
}

export interface SectionDetail {
    section: string;
    total: number;
    excited: number;
    content: number;
    stressed: number;
    drained: number;
    atRisk: number;
    students: SectionStudentRow[];
}

export interface MoodTrendPoint {
    label: string;
    score: number | null;
}

export interface RecentMoodLog {
    id: number;
    mood: string;
    content: string | null;
    date: string;
}

// ── Appointments ─────────────────────────────────────────────────────────────

export type AppointmentTab = 'requests' | 'scheduled' | 'history' | 'rejected' | 'missed';
export type AppointmentStatus =
    | 'Pending'
    | 'Scheduled'
    | 'Completed'
    | 'Rejected';

export interface Appointment {
    id: number;
    student_name: string;
    context: string;
    note: string | null;
    date: string;
    time: string;
    status: AppointmentStatus;
    student_profile: AppointmentStudentProfile;
}

export interface AvailableSlot {
    id: number;
    date: string;
    start_time: string;
    taken: boolean;
}

export interface AppointmentStudentProfile {
    initials: string;
    section: string;
    year_level: string;
    email: string;
    student_id: string;
    total_appointments: number;
    history: Array<{
        context: string;
        date: string;
        time: string;
        note: string | null;
        status: AppointmentStatus;
    }>;
}

export interface AppointmentTabCounts {
    requests: number;
    scheduled: number;
    history: number;
    rejected: number;
    missed: number;
}

export interface AppointmentFilters {
    tab: AppointmentTab;
}

export interface StudentMoodReport {
    id: number;
    name: string;
    fullName: string;
    studentNumber: string;
    yearLevel: string;
    section: string;
    initials: string;
    moodSummary: {
        excited: number;
        content: number;
        stressed: number;
        drained: number;
    };
    summaryStats: {
        totalMoodLogs: number;
        totalPosts: number;
        flaggedPosts: number;
    };
    trend: 'Declining' | 'Stable' | 'Improving';
    trendData: MoodTrendPoint[];
    recentLogs: RecentMoodLog[];
}

// ── Dashboard ────────────────────────────────────────────────────────────────

export interface DashboardMoodEntry {
    id: number;
    mood: Mood;
    message: string | null;
    time: string;
    name: string;
    flagged: boolean;
}

export interface DashboardAppointment {
    id: number;
    name: string;
    time: string;
    date: string;
    label: 'Urgent' | 'Consultation';
    style: string;
}

export interface MoodDistributionBar {
    label: string;
    pct: number;
    color: string;
}

export interface MoodTrendsData {
    period: 'Today' | 'Weekly' | 'Monthly';
    section: string;
    sections: string[];
    total: number;
    distribution: MoodDistributionBar[];
}

/** Props for the Dashboard page. */
export interface DashboardPageProps {
    moodLogsToday: number;
    activeStudents: number;
    flaggedPosts: number;
    escalationRequests: number;
    moodEntries: DashboardMoodEntry[];
    appointments: DashboardAppointment[];
    moodTrends: MoodTrendsData;
}
