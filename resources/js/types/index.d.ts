// ── Shared / Core ─────────────────────────────────────────────────────────────

export interface User {
    id: number;
    name: string;
    email: string;
    avatar?: string | null;
    email_verified_at?: string;
}

export type PageProps<T extends Record<string, unknown> = Record<string, unknown>> = T & {
    auth: {
        user: User;
    };
    assets: {
        logo: string;
    };
    flash: {
        error?: string;
        success?: string;
        student_credentials?: { email: string; password: string } | null;
    };
};

export interface AdminNotification {
    id: number;
    title: string | null;
    content: string | null;
    type: string;
    is_seen: boolean;
    datetime: string | null;
}

export interface AdminNotificationFeed {
    notifications: AdminNotification[];
    unread: number;
}

export interface FormErrors {
    email?: string;
    password?: string;
    [key: string]: string | undefined;
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

// ── Auth ──────────────────────────────────────────────────────────────────────

export interface LoginPageProps {
    can_reset_password?: boolean;
    status?: string;
}

// ── Profile & Admin ───────────────────────────────────────────────────────────

export interface AdminProfile {
    first_name: string;
    last_name: string;
    phone: string;
    role: string;
    department: string;
}

export interface AdminSummary {
    first_name: string;
    last_name: string;
    phone: string | null;
    avatar: string | null;
    role: string;
    status: string;
}

export interface PasswordForm {
    current: string;
    new_pass: string;
    confirm: string;
}

export interface NotificationPreferences {
    new_flags: boolean;
    appointments: boolean;
    escalations: boolean;
    weekly_reports: boolean;
    system_updates: boolean;
}

export interface ProfileSettingsPageProps {
    admin: AdminSummary | null;
    notifications?: Partial<NotificationPreferences>;
    status?: string;
}

// ── Posts / MoodSpace ─────────────────────────────────────────────────────────

export type Mood = 'Drained' | 'Stressed' | 'Content' | 'Excited';
export type PostStatus = 'flagged' | 'safe';
export type PostFilter = 'all' | 'flagged' | 'safe';

export interface PostFilters {
    status: string | null;
    program: string | null;
    mood: string | null;
    sort: string | null;
    tab: string | null;
}

export interface Post {
    id: number;
    anonymous_name: string | null;
    last_name: string;
    first_name: string;
    program: string;
    time: string;
    date: string;
    mood: string;
    status: 'flagged' | 'safe';
    content: string | null;
}

// ── Students / User Accounts ──────────────────────────────────────────────────

export type VerificationStatus = 'pending' | 'verified' | 'unverified';
export type AccountStatus = 'active' | 'suspended';
export type StudentTab = 'all' | 'pending' | 'verified' | 'suspended';

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
    program: string;
    verification_status: VerificationStatus;
    account_status: AccountStatus;
}

export interface StudentAccountFilters {
    search: string | null;
    year_level: number | null;
    tab: string;
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
    total_mood_logs: number;
    avg_daily_logs: number;
    at_risk_students: number;
    appointments_set: number;
    distribution: MoodDistributionItem[];
}

export interface ProgramSummary {
    program: string;
    total: number;
    excited: number;
    content: number;
    stressed: number;
    drained: number;
    at_risk: number;
}

export interface AtRiskStudent {
    id: number;
    name: string;
    student_number: string;
    program: string;
    window_a_count: number;
    moods: string[];
    warning_mood_counts: Record<string, number>;
    time_at_risk: string;
    last_log: string;
    has_consultation: boolean;
}

export interface ProgramStudentRow {
    id: number;
    name: string;
    initials: string;
    student_number: string;
    year_level: string;
    trend: 'Declining' | 'Stable' | 'Improving';
}

export interface ProgramDetail {
    program: string;
    total: number;
    excited: number;
    content: number;
    stressed: number;
    drained: number;
    at_risk: number;
    students: ProgramStudentRow[];
}

export interface MoodTrendPoint {
    label: string;
    score: number | null;
}

export interface RecentMoodEntry {
    id: number;
    mood: string;
    content: string | null;
    date: string;
}

export interface StudentMoodReport {
    id: number;
    name: string;
    full_name: string;
    student_number: string;
    year_level: string;
    program: string;
    initials: string;
    mood_summary: {
        excited: number;
        content: number;
        stressed: number;
        drained: number;
    };
    summary_stats: {
        total_mood_entries: number;
        total_posts: number;
        flagged_posts: number;
    };
    trend: 'Declining' | 'Stable' | 'Improving';
    trend_data: MoodTrendPoint[];
    recent_entries: RecentMoodEntry[];
}

// ── Appointments ──────────────────────────────────────────────────────────────

export type AppointmentTab = 'requests' | 'scheduled' | 'history' | 'rejected' | 'missed';
export type AppointmentStatus =
    | 'Pending'
    | 'Scheduled'
    | 'Completed'
    | 'Rejected';

export interface Appointment {
    id: number;
    student_id: number;
    student_name: string;
    context: string;
    note: string | null;
    date: string;
    time: string;
    status: AppointmentStatus;
    student_summary: AppointmentStudentSummary;
    can_check_in?: boolean;
    checkin_url?: string | null;
    checkin_expires_at?: string | null;
}

export interface AvailableSlot {
    id: number;
    date: string;
    start_time: string;
    taken: boolean;
}

export interface CheckInReadyAppointment {
    id: number;
    student_name: string;
    date: string;
    time: string;
    checkin_url: string | null;
    checkin_expires_at?: string | null;
}

export interface AppointmentStudentSummary {
    initials: string;
    program: string;
    year_level: string;
    student_id: string;
    total_appointments: number;
}

export interface AppointmentHistoryEntry {
    context: string;
    date: string;
    time: string;
    note: string | null;
    status: AppointmentStatus;
}

export interface AppointmentStudentProfile {
    name: string;
    initials: string;
    program: string;
    year_level: string;
    student_id: string;
    total_appointments: number;
    history: AppointmentHistoryEntry[];
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

// ── Dashboard ─────────────────────────────────────────────────────────────────

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
    program: string;
    programs: string[];
    total: number;
    distribution: MoodDistributionBar[];
}

export interface DashboardMoodLogsBreakdown {
    total: number;
    safe: number;
    flagged: number;
    leading: string | null;
}

export interface DashboardStudentsBreakdown {
    total: number;
    active: number;
    pending: number;
    suspended: number;
}

export interface DashboardPostsBreakdown {
    total: number;
    safe: number;
    flagged: number;
    flag_rate: number;
}

export interface DashboardAppointmentsBreakdown {
    total: number;
    scheduled: number;
    pending: number;
    missed: number;
}

export interface DashboardRecentAppointment {
    id: number;
    name: string;
    context: string | null;
    time: string;
    status: 'Scheduled' | 'Pending' | 'Missed';
}

/** Props for the Dashboard page. */
export interface DashboardPageProps {
    mood_logs_today: number;
    active_students: number;
    flagged_posts: number;
    escalation_requests: number;
    mood_entries: DashboardMoodEntry[];
    appointments: DashboardAppointment[];
    mood_trends: MoodTrendsData;
    mood_logs_breakdown: DashboardMoodLogsBreakdown;
    students_breakdown: DashboardStudentsBreakdown;
    posts_breakdown: DashboardPostsBreakdown;
    appointments_breakdown: DashboardAppointmentsBreakdown;
    activity_appointments: DashboardRecentAppointment[];
}

// ── Reported Posts ────────────────────────────────────────────────────────────

export type ReportStatus = 'pending' | 'flagged' | 'resolved';

export type ReportReason =
    | 'Harassment'
    | 'Offensive Language'
    | 'Bullying'
    | 'False Information'
    | 'Spam'
    | 'Other';

export interface Reporter {
    id: number;
    name: string;
    student_number: string;
    program: string;
    date_reported: string;
    reason: ReportReason;
    comment?: string;
}

export interface ReasonBreakdown {
    harassment: number;
    offensive_language: number;
    bullying: number;
    false_information: number;
    spam: number;
    other: number;
}

export interface ReportedPost {
    id: number;
    anonymous_name: string;
    post_preview: string;
    full_content: string;
    date_posted: string;
    program: string;
    mood: string;
    report_count: number;
    top_reason: ReportReason;
    latest_report_date: string;
    status: ReportStatus;
    reason_breakdown: ReasonBreakdown;
    reporters: Reporter[];
}
