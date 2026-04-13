import { Head } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import { index, show } from '@/routes/issues';
import type { BreadcrumbItem } from '@/types';

interface Issue {
    id: number;
    title: string;
    subtitle: string | null;
}

interface Environment {
    id: number;
    slug: string;
    name: string;
}

interface Props {
    environment: Environment;
    issue: Issue;
}

export default function IssueShow({ environment, issue }: Props) {
    const baseUrl = index.url(environment);
    const issueUrl = show.url({ environment, issue: issue.id });

    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Issues', href: baseUrl },
        { title: `#${issue.id}`, href: issueUrl },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={issue.title} />
            <div className="p-6">
                <h1 className="text-3xl font-semibold break-all">
                    {issue.subtitle
                        ? `${issue.title}: ${issue.subtitle}`
                        : issue.title}
                </h1>
            </div>
        </AppLayout>
    );
}
