import { Head, usePage, router } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
import AuthenticatedLayout from '@/layouts/authenticated-layout';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Tooltip, TooltipContent, TooltipProvider, TooltipTrigger } from '@/components/ui/tooltip';
import { Calendar as CalendarIcon, Clock, Users, List, Kanban } from 'lucide-react';
import CalendarView from '@/components/calendar-view';
import { formatDate, formatTime } from '@/utils/helpers';

interface CalendarEvent {
  id: number | string;
  title: string;
  startDate: string;
  endDate: string;
  time: string;
  description: string;
  type: string;
  color: string;
  attendees: string[];
}

interface Project {
  id: number;
  name: string;
}

interface CalendarProps {
  project: Project;
  events: CalendarEvent[];
  auth: { user?: { permissions?: string[] } };
}

export default function Calendar() {
  const { t } = useTranslation();
  const { project, events, auth } = usePage<CalendarProps>().props;

  return (
    <AuthenticatedLayout
      breadcrumbs={[
        { label: t('Project'), url: route('project.index') },
        { label: project.name, url: route('project.show', project.id) },
        { label: t('Task Calendar') }
      ]}
      pageTitle={`${project.name} - ${t('Task Calendar')}`}
      backUrl={route('project.show', project.id)}
      pageActions={
        <div className="flex gap-2">
          <TooltipProvider>
            {auth.user?.permissions?.includes('manage-project-task') && (
              <Tooltip delayDuration={0}>
                <TooltipTrigger asChild>
                  <Button size="sm" onClick={() => router.get(route('project.tasks.index', { project_id: project.id }))}>
                    <List className="h-4 w-4" />
                  </Button>
                </TooltipTrigger>
                <TooltipContent>
                  <p>{t('List View')}</p>
                </TooltipContent>
              </Tooltip>
            )}
            {auth.user?.permissions?.includes('manage-project-task') && (
              <Tooltip delayDuration={0}>
                <TooltipTrigger asChild>
                  <Button size="sm" onClick={() => router.get(route('project.tasks.kanban', project.id))}>
                    <Kanban className="h-4 w-4" />
                  </Button>
                </TooltipTrigger>
                <TooltipContent>
                  <p>{t('Kanban View')}</p>
                </TooltipContent>
              </Tooltip>
            )}
          </TooltipProvider>
        </div>
      }
    >
      <Head title={t('Task Calendar')} />

      <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div className="lg:col-span-2">
          <CalendarView events={events} />
        </div>

        <div className="space-y-4">
          <Card>
            <CardHeader>
              <CardTitle className="flex items-center gap-2 text-base">
                <Clock className="h-4 w-4" />
                {t('All Tasks')}
              </CardTitle>
            </CardHeader>
            <CardContent className="max-h-[75vh] overflow-y-auto">
              {events.length === 0 ? (
                <div className="text-center py-8 text-gray-500">
                  <CalendarIcon className="h-12 w-12 mx-auto mb-2 opacity-50" />
                  <p>{t('No Tasks')}</p>
                </div>
              ) : (
                <div className="space-y-4">
                  {events.map(event => (
                    <div key={event.id} className="border rounded-lg p-4">
                      <div className="flex items-start justify-between mb-2">
                        <h4 className="font-medium">{event.title}</h4>
                        <Badge style={{ backgroundColor: `${event.color}1A`, color: event.color }}>
                          {event.type}
                        </Badge>
                      </div>

                      <div className="flex items-center justify-between mb-2">
                        <div className="flex items-center gap-2 text-sm text-gray-600">
                          <Clock className="h-4 w-4" />
                          <span>
                            {event.startDate === event.endDate ?
                              `${formatDate(event.startDate)} - ${formatTime(event.time)}` :
                              `${formatDate(event.startDate)} - ${formatDate(event.endDate)}`
                            }
                          </span>
                        </div>
                      </div>

                      <p className="text-sm text-gray-600 mb-3">{event.description}</p>
                    </div>
                  ))}
                </div>
              )}
            </CardContent>
          </Card>
        </div>
      </div>
    </AuthenticatedLayout>
  );
}
