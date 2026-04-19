export type TimelineUser = {
    id: number;
    name: string;
    email: string;
};

export type IssueCreatedEntry = {
    kind: 'issue_created';
    data: Record<string, never>;
};

export type StatusChangedEntry = {
    kind: 'status_changed';
    data: { from: string; to: string };
};

export type AssignmentChangedEntry = {
    kind: 'assignment_changed';
    data: {
        from_user: TimelineUser | null;
        to_user: TimelineUser | null;
    };
};

export type CommentEntry = {
    kind: 'comment';
    data: {
        id: number;
        body: string | null;
        edited_at: string | null;
        deleted: boolean;
        can_edit: boolean;
    };
};

export type TimelineEntry = {
    id: number;
    occurred_at: string;
    actor: TimelineUser | null;
} & (IssueCreatedEntry | StatusChangedEntry | AssignmentChangedEntry | CommentEntry);
