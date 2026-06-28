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
