import { LucideIcon } from 'lucide-react';

export interface Auth {
    user: User;
}

export interface BreadcrumbItem {
    title: string;
    href: string;
}

export interface NavGroup {
    title: string;
    items: NavItem[];
}

export interface NavItem {
    title: string;
    url: string;
    icon?: LucideIcon | null;
    isActive?: boolean;
}

export interface SharedData {
    name: string;
    quote: { message: string; author: string };
    auth: Auth;
    flash: { success?: string | null; error?: string | null };
    [key: string]: unknown;
}

export interface User {
    id: number;
    name: string;
    email: string;
    avatar?: string;
    email_verified_at: string | null;
    roles: string[];
    club_id: number | null;
    club?: Club | null;
    created_at: string;
    updated_at: string;
    [key: string]: unknown; // This allows for additional properties...
}

export interface Club {
    id: number;
    name: string;
    city: string | null;
    country: string | null;
    logo_path: string | null;
    [key: string]: unknown;
}

export interface Achievement {
    id: number;
    code: string;
    name: string;
    description: string | null;
    icon: string | null;
    xp_reward: number;
    pivot?: { unlocked_at: string };
    [key: string]: unknown;
}

export interface Player {
    id: number;
    user_id: number;
    club_id: number | null;
    handedness: 'left' | 'right' | null;
    playing_style: string | null;
    height_cm: number | null;
    bio: string | null;
    rating_current: number;
    rating_deviation: number;
    matches_played_rated: number;
    matches_played: number;
    matches_won: number;
    xp_total: number;
    level: number;
    is_elite: boolean;
    user?: User;
    club?: Club | null;
    achievements?: Achievement[];
    [key: string]: unknown;
}

export type TournamentStatus = 'draft' | 'registration_open' | 'registration_closed' | 'in_progress' | 'completed' | 'cancelled';

export type DivisionCategoryType = 'singles' | 'doubles' | 'team';
export type DivisionGenderCategory = 'open' | 'male' | 'female' | 'mixed';
export type DivisionFormat = 'single_elimination' | 'double_elimination' | 'round_robin' | 'swiss' | 'group_knockout';
export type DivisionStatus = 'pending_draw' | 'drawn' | 'in_progress' | 'completed';

export interface TournamentDivision {
    id: number;
    tournament_id: number;
    name: string;
    category_type: DivisionCategoryType;
    gender_category: DivisionGenderCategory;
    min_age: number | null;
    max_age: number | null;
    format: DivisionFormat;
    best_of: number;
    points_to_win: number;
    group_size: number | null;
    advance_per_group: number | null;
    swiss_rounds: number | null;
    max_participants: number | null;
    seed_by_rating: boolean;
    status: DivisionStatus;
    display_order: number;
    rounds?: BracketRound[];
    groups?: BracketGroup[];
    [key: string]: unknown;
}

export type RegistrationFieldType = 'text' | 'textarea' | 'number' | 'email' | 'phone' | 'date' | 'select' | 'radio' | 'checkbox' | 'checkbox_group';

export interface TournamentRegistrationField {
    id: number;
    tournament_id: number;
    label: string;
    field_type: RegistrationFieldType;
    options: string[] | null;
    placeholder: string | null;
    help_text: string | null;
    is_required: boolean;
    display_order: number;
    [key: string]: unknown;
}

export interface DivisionTemplate {
    id: number;
    created_by: number;
    name: string;
    category_type: DivisionCategoryType;
    gender_category: DivisionGenderCategory;
    min_age: number | null;
    max_age: number | null;
    format: DivisionFormat;
    best_of: number;
    points_to_win: number;
    group_size: number | null;
    advance_per_group: number | null;
    swiss_rounds: number | null;
    max_participants: number | null;
    seed_by_rating: boolean;
    [key: string]: unknown;
}

export interface FormTemplateField {
    id: number;
    form_template_id: number;
    label: string;
    field_type: RegistrationFieldType;
    options: string[] | null;
    placeholder: string | null;
    help_text: string | null;
    is_required: boolean;
    display_order: number;
    [key: string]: unknown;
}

export interface FormTemplate {
    id: number;
    created_by: number;
    name: string;
    description: string | null;
    fields?: FormTemplateField[];
    fields_count?: number;
    [key: string]: unknown;
}

export type RegistrationStatus = 'pending' | 'approved' | 'rejected' | 'waitlisted' | 'cancelled';

export interface TournamentRegistrationDivision {
    id: number;
    tournament_registration_id: number;
    tournament_division_id: number;
    partner_name: string | null;
    partner_club: string | null;
    seed_rating_snapshot: number | null;
    division?: TournamentDivision;
    [key: string]: unknown;
}

export interface TournamentRegistrationResponse {
    id: number;
    tournament_registration_id: number;
    tournament_registration_field_id: number;
    value: string | null;
    field?: TournamentRegistrationField;
    [key: string]: unknown;
}

export interface TournamentRegistration {
    id: number;
    tournament_id: number;
    player_id: number;
    status: RegistrationStatus;
    submitted_at: string;
    reviewed_by: number | null;
    reviewed_at: string | null;
    review_notes: string | null;
    player?: Player;
    tournament?: Tournament;
    divisions?: TournamentRegistrationDivision[];
    responses?: TournamentRegistrationResponse[];
    [key: string]: unknown;
}

export interface Tournament {
    id: number;
    name: string;
    slug: string;
    description: string | null;
    venue: string | null;
    city: string | null;
    cover_image_path: string | null;
    club_id: number | null;
    created_by: number;
    status: TournamentStatus;
    start_date: string;
    end_date: string;
    registration_opens_at: string | null;
    registration_closes_at: string | null;
    max_participants: number | null;
    divisions?: TournamentDivision[];
    registration_fields?: TournamentRegistrationField[];
    divisions_count?: number;
    registrations_count?: number;
    [key: string]: unknown;
}

export type MatchStatus = 'pending' | 'ready' | 'in_progress' | 'completed' | 'walkover' | 'cancelled';
export type RoundStage = 'group_stage' | 'swiss' | 'winners_bracket' | 'losers_bracket' | 'main_bracket' | 'grand_final';

export interface BracketRound {
    id: number;
    tournament_division_id: number;
    stage: RoundStage;
    round_number: number;
    name: string | null;
    [key: string]: unknown;
}

export interface BracketGroup {
    id: number;
    tournament_division_id: number;
    name: string;
    display_order: number;
    [key: string]: unknown;
}

export interface BracketMatch {
    id: number;
    round_id: number | null;
    round_name: string | null;
    round_number: number | null;
    stage: RoundStage | null;
    group_id: number | null;
    group_name: string | null;
    match_number: number;
    entrant1_id: number | null;
    entrant2_id: number | null;
    entrant1_name: string;
    entrant2_name: string;
    status: MatchStatus;
    winner_entrant_id: number | null;
    referee_id: number | null;
    referee_name: string | null;
    [key: string]: unknown;
}

export interface RefereeOption {
    id: number;
    name: string;
}

export interface StandingRow {
    entrant_id: number;
    name: string;
    played: number;
    wins: number;
    losses: number;
    points: number;
    [key: string]: unknown;
}

export interface MatchGameState {
    id: number;
    game_number: number;
    entrant1_points: number;
    entrant2_points: number;
    winner_entrant_id: number | null;
    first_server_entrant_id: number | null;
}

export interface MatchScoreState {
    id: number;
    status: MatchStatus;
    entrant1_id: number | null;
    entrant2_id: number | null;
    entrant1_name: string;
    entrant2_name: string;
    best_of: number;
    points_to_win: number;
    score_summary: string | null;
    winner_entrant_id: number | null;
    games: MatchGameState[];
    current_game_number: number | null;
    current_server_entrant_id: number | null;
    expedite_active: boolean;
    expedite_seconds_remaining: number | null;
    is_deciding_game: boolean;
    deciding_game_ends_switched: boolean;
    [key: string]: unknown;
}

export interface PaginatedData<T> {
    data: T[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    links: { url: string | null; label: string; active: boolean }[];
    [key: string]: unknown;
}
