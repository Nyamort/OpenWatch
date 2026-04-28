import { usePage } from '@inertiajs/react';
import {
    AlertCircle,
    ArrowUpRight,
    Bug,
    Bell,
    BriefcaseBusiness,
    CalendarClock,
    Database,
    Globe,
    HardDrive,
    LayoutGrid,
    Mail,
    ScrollText,
    Terminal,
    Users,
} from 'lucide-react';
import { useState } from 'react';
import { NavMain } from '@/components/layout/nav-main';
import { NavUser } from '@/components/layout/nav-user';
import { ContextSelector } from '@/components/organizations/context-selector';
import { SetupWizardDialog } from '@/components/setup-wizard-dialog';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
} from '@/components/ui/sidebar';
import { useAnalyticsHref } from '@/hooks/use-analytics-href';
import { dashboard } from '@/routes';
import { index as cacheEventsIndex } from '@/routes/analytics/cache-events';
import { index as commandsIndex } from '@/routes/analytics/commands';
import { index as exceptionsIndex } from '@/routes/analytics/exceptions';
import { index as jobsIndex } from '@/routes/analytics/jobs';
import { index as logsIndex } from '@/routes/analytics/logs';
import { index as mailIndex } from '@/routes/analytics/mail';
import { index as notificationsIndex } from '@/routes/analytics/notifications';
import { index as outgoingRequestsIndex } from '@/routes/analytics/outgoing-requests';
import { index as queriesIndex } from '@/routes/analytics/queries';
import { index as requestsIndex } from '@/routes/analytics/requests';
import { index as scheduledTasksIndex } from '@/routes/analytics/scheduled-tasks';
import { index as usersIndex } from '@/routes/analytics/users';
import { index as issuesIndex } from '@/routes/issues';
import type { NavItem } from '@/types/navigation';

export function AppSidebar() {
    const [wizardOpen, setWizardOpen] = useState(false);
    const { props } = usePage();
    const { activeOrganization, activeProject, activeEnvironment } = props as {
        activeOrganization?: { slug: string } | null;
        activeProject?: { slug: string } | null;
        activeEnvironment?: { slug: string } | null;
    };

    const hasContext = !!(
        activeOrganization &&
        activeProject &&
        activeEnvironment
    );

    const analyticsHref = useAnalyticsHref();

    const navGroups: { label?: string; items: NavItem[] }[] = [
        {
            items: [
                {
                    title: 'Dashboard',
                    href: analyticsHref(dashboard.url()),
                    icon: LayoutGrid,
                },
                ...(hasContext
                    ? [
                          {
                              title: 'Issues',
                              href: issuesIndex.url(activeEnvironment!.slug),
                              icon: Bug,
                          },
                      ]
                    : []),
            ],
        },
        ...(hasContext
            ? [
                  {
                      label: 'Activity',
                      items: [
                          {
                              title: 'Requests',
                              href: analyticsHref(
                                  requestsIndex.url({
                                      environment: activeEnvironment!.slug,
                                  }),
                              ),
                              icon: Globe,
                          },
                          {
                              title: 'Jobs',
                              href: analyticsHref(
                                  jobsIndex.url({
                                      environment: activeEnvironment!.slug,
                                  }),
                              ),
                              icon: BriefcaseBusiness,
                          },
                          {
                              title: 'Commands',
                              href: analyticsHref(
                                  commandsIndex.url({
                                      environment: activeEnvironment!.slug,
                                  }),
                              ),
                              icon: Terminal,
                          },
                          {
                              title: 'Scheduled Tasks',
                              href: analyticsHref(
                                  scheduledTasksIndex.url({
                                      environment: activeEnvironment!.slug,
                                  }),
                              ),
                              icon: CalendarClock,
                          },
                          {
                              title: 'Exceptions',
                              href: analyticsHref(
                                  exceptionsIndex.url({
                                      environment: activeEnvironment!.slug,
                                  }),
                              ),
                              icon: AlertCircle,
                          },
                          {
                              title: 'Queries',
                              href: analyticsHref(
                                  queriesIndex.url({
                                      environment: activeEnvironment!.slug,
                                  }),
                              ),
                              icon: Database,
                          },
                          {
                              title: 'Notifications',
                              href: analyticsHref(
                                  notificationsIndex.url({
                                      environment: activeEnvironment!.slug,
                                  }),
                              ),
                              icon: Bell,
                          },
                          {
                              title: 'Mails',
                              href: analyticsHref(
                                  mailIndex.url({
                                      environment: activeEnvironment!.slug,
                                  }),
                              ),
                              icon: Mail,
                          },
                          {
                              title: 'Cache',
                              href: analyticsHref(
                                  cacheEventsIndex.url({
                                      environment: activeEnvironment!.slug,
                                  }),
                              ),
                              icon: HardDrive,
                          },
                          {
                              title: 'Outgoing Requests',
                              href: analyticsHref(
                                  outgoingRequestsIndex.url({
                                      environment: activeEnvironment!.slug,
                                  }),
                              ),
                              icon: ArrowUpRight,
                          },
                      ],
                  },
                  {
                      label: 'Monitoring',
                      items: [
                          {
                              title: 'Users',
                              href: analyticsHref(
                                  usersIndex.url({
                                      environment: activeEnvironment!.slug,
                                  }),
                              ),
                              icon: Users,
                          },
                          {
                              title: 'Logs',
                              href: analyticsHref(
                                  logsIndex.url({
                                      environment: activeEnvironment!.slug,
                                  }),
                              ),
                              icon: ScrollText,
                          },
                      ],
                  },
              ]
            : []),
    ];

    return (
        <>
            <SetupWizardDialog open={wizardOpen} onOpenChange={setWizardOpen} />
            <Sidebar collapsible="icon" variant="inset">
                <SidebarHeader>
                    <ContextSelector
                        onNewApplication={() => setWizardOpen(true)}
                    />
                </SidebarHeader>

                <SidebarContent>
                    {navGroups.map((group, i) => (
                        <NavMain
                            key={group.label ?? i}
                            label={group.label}
                            items={group.items}
                        />
                    ))}
                </SidebarContent>

                <SidebarFooter>
                    <NavUser />
                </SidebarFooter>
            </Sidebar>
        </>
    );
}
