export type MoodPalette = 'excited' | 'content' | 'drained' | 'stressed';
export type StepBadgeTone = 'olive' | 'amber' | 'evergreen';

export interface HeroHighlight {
    value: string;
    caption: string;
    emphasized?: boolean;
}

export interface MoodSpectrumEntry {
    palette: MoodPalette;
    glyph: string;
    label: string;
    sensation: string;
}

export interface WellnessModuleEntry {
    glyph: string;
    title: string;
    summary: string;
}

export interface GettingStartedStep {
    position: number;
    badgeTone: StepBadgeTone;
    title: string;
    detail: string;
}

export interface AndroidReleaseFact {
    label: string;
    value: string;
}

export function useMarketingLandingContent() {
    const heroHighlights: HeroHighlight[] = [
        { value: '5', caption: 'Connected modules' },
        { value: '4', caption: 'Doors per check-in' },
        { value: '100%', caption: 'Confidential', emphasized: true },
    ];

    const moodSpectrumEntries: MoodSpectrumEntry[] = [
        { palette: 'excited', glyph: '⚡', label: 'Excited', sensation: 'Buzzing, charged, full of fuel' },
        { palette: 'content', glyph: '🍀', label: 'Content', sensation: 'Calm, settled, at ease' },
        { palette: 'drained', glyph: '🌧️', label: 'Drained', sensation: 'Tired, sluggish, running low' },
        { palette: 'stressed', glyph: '😫', label: 'Stressed', sensation: 'Tense, on-edge, overwhelmed' },
    ];

    const wellnessModuleEntries: WellnessModuleEntry[] = [
        {
            glyph: '📋',
            title: 'Mood Check-In',
            summary: 'Four quick "doors" — Energy, Duration, Heart, and Context — capture how you really feel in under a minute.',
        },
        {
            glyph: '📓',
            title: 'My Journal',
            summary: 'Weekly, monthly, and yearly mood trends, a color heatmap, check-in streaks, and every entry in one private place.',
        },
        {
            glyph: '🌸',
            title: 'MoodSpace',
            summary: 'A safe, anonymous space to share how you feel and react to others. Every post is screened by the GCU before it goes live.',
        },
        {
            glyph: '📅',
            title: 'Appointments',
            summary: 'Book a confidential session with a GCU counselor, see your scheduled visits, and get gentle reminders before each one.',
        },
        {
            glyph: '💬',
            title: 'MoLi · Wellness AI',
            summary: 'An always-on companion for emotional support that remembers your recent moods — never a replacement for a real counselor.',
        },
        {
            glyph: '🔔',
            title: 'Smart Alerts',
            summary: 'Stay updated on appointments set by the GCU, MoodSpace post approvals, and daily check-in reminders — all in one hub.',
        },
    ];

    const gettingStartedSteps: GettingStartedStep[] = [
        {
            position: 1,
            badgeTone: 'olive',
            title: 'Download the APK',
            detail: 'Get the official MoodLink build for Android from this page and install it on your phone.',
        },
        {
            position: 2,
            badgeTone: 'amber',
            title: 'Verify with the GCU',
            detail: 'Submit your name, year, program, and student number. The Guidance & Counseling Unit reviews and approves your account.',
        },
        {
            position: 3,
            badgeTone: 'evergreen',
            title: 'Sign in & check in',
            detail: 'Log in with your FEU student credentials and start your first daily mood check-in right from the home screen.',
        },
    ];

    const androidReleaseFacts: AndroidReleaseFact[] = [
        { label: 'Version', value: '2.1.0' },
        { label: 'Size', value: '24 MB' },
        { label: 'Requires', value: 'Android 8.0+' },
        { label: 'Updated', value: 'Jun 2026' },
    ];

    return {
        heroHighlights,
        moodSpectrumEntries,
        wellnessModuleEntries,
        gettingStartedSteps,
        androidReleaseFacts,
    };
}
