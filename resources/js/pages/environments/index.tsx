import { Head } from '@inertiajs/react';
import { Badge } from '@/components/ui/badge';
import AppLayout from '@/layouts/app-layout';
import type { BreadcrumbItem } from '@/types';

interface Organization {
    id: number;
    name: string;
    slug: string;
}

interface Project {
    id: number;
    name: string;
    slug: string;
}

interface Environment {
    id: number;
    name: string;
    slug: string;
    type: string;
    status: string;
}

interface Props {
    organization: Organization;
    project: Project;
    environments: Environment[];
}

function statusVariant(status: string): 'default' | 'secondary' | 'destructive' | 'outline' {
    if (status === 'active') {
        return 'secondary';
    }
    return 'outline';
}

export default function EnvironmentsIndex({ organization, project, environments }: Props) {
    const orgUrl = `/organizations/${organization.slug}`;
    const projectUrl = `${orgUrl}/projects/${project.slug}`;
    const environmentsUrl = `${projectUrl}/environments`;

    const breadcrumbs: BreadcrumbItem[] = [
        { title: organization.name, href: orgUrl },
        { title: project.name, href: projectUrl },
        { title: 'Environments', href: environmentsUrl },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Environments" />
            <div className="flex flex-col gap-6 p-6">
                <div className="flex items-center justify-between">
                    <h1 className="text-2xl font-semibold text-foreground">Environments</h1>
                </div>

                {environments.length === 0 ? (
                    <div className="rounded-lg border border-dashed p-12 text-center">
                        <p className="text-muted-foreground">No environments yet.</p>
                        <p className="mt-1 text-sm text-muted-foreground">Use the setup wizard to create your first environment.</p>
                    </div>
                ) : (
                    <div className="rounded-lg border">
                        <table className="w-full text-sm">
                            <thead className="border-b bg-muted/40">
                                <tr>
                                    <th className="px-4 py-3 text-left font-medium">Name</th>
                                    <th className="px-4 py-3 text-left font-medium">Slug</th>
                                    <th className="px-4 py-3 text-left font-medium">Type</th>
                                    <th className="px-4 py-3 text-left font-medium">Status</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y">
                                {environments.map((env) => (
                                    <tr key={env.id} className="hover:bg-muted/20">
                                        <td className="px-4 py-3">
                                            <a href={`${environmentsUrl}/${env.slug}`} className="font-medium hover:underline">
                                                {env.name}
                                            </a>
                                        </td>
                                        <td className="px-4 py-3 text-muted-foreground">{env.slug}</td>
                                        <td className="px-4 py-3">
                                            <Badge variant="outline" className="capitalize">{env.type}</Badge>
                                        </td>
                                        <td className="px-4 py-3">
                                            <Badge variant={statusVariant(env.status)} className="capitalize">{env.status}</Badge>
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                )}
            </div>
        </AppLayout>
    );
}
