import { Avatar, AvatarFallback } from '@/components/ui/avatar';
import { cn } from '@/lib/utils';
import type { TimelineUser } from '@/types/timeline';

interface Props {
    user: TimelineUser | null;
    size?: 'sm' | 'md';
}

export function UserAvatar({ user, size = 'md' }: Props) {
    const initial = (user?.name ?? '?').charAt(0).toUpperCase();

    return (
        <Avatar
            className={cn(
                'ring-2 ring-background',
                size === 'sm' ? 'size-6' : 'size-7',
            )}
        >
            <AvatarFallback className="bg-muted text-xs font-medium">
                {initial}
            </AvatarFallback>
        </Avatar>
    );
}
