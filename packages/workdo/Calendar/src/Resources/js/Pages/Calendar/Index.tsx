import { useState } from 'react';
import { Head, router } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
import AuthenticatedLayout from '@/layouts/authenticated-layout';
import { Card } from '@/components/ui/card';
import { Label } from '@/components/ui/label';
import { RadioGroup, RadioGroupItem } from '@/components/ui/radio-group';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import CalendarView from '@/components/calendar-view';

interface CalendarEvent {
  id: number;
  title: string;
  start: string;
  end: string;
  description?: string;
  event_type: string;
  startDate: string;
  endDate: string;
  time: string;
  type: string;
  color: string;
  attendees: string[];
}

interface CalendarIndexProps {
  events: CalendarEvent[];
  filters: {
    calendar_type: string;
    module_filter: string;
  };
  availableFilters: Record<string, string>;
  hasGoogleCalendarConfig: boolean;
}

export default function CalendarIndex({ events, filters, availableFilters, hasGoogleCalendarConfig }: CalendarIndexProps) {
  const { t } = useTranslation();
  const [calendarType, setCalendarType] = useState(filters.calendar_type);
  const [moduleFilter, setModuleFilter] = useState(filters.module_filter);

  const breadcrumbs = [
    { label: t('Calendar') }
  ];

  const handleFilterChange = (type: string, value: string) => {
    const params = new URLSearchParams(window.location.search);
    params.set(type, value);
    router.get(route('calendar.view.index'), Object.fromEntries(params), {
      preserveState: true,
      preserveScroll: true,
      onError: (errors) => {
        console.error('Calendar filter error:', errors);
      }
    });
  };

  const handleEventClick = (clickInfo: any) => {
    alert(`Event: ${clickInfo.event.title}\nDescription: ${clickInfo.event.extendedProps.description || 'No description'}`);
  };

  const handleDateSelect = (selectInfo: any) => {
    const title = prompt('Please enter event title:');
    if (title) {
      console.log('Create event:', {
        title,
        start: selectInfo.startStr,
        end: selectInfo.endStr,
      });
    }
  };


  return (
    <AuthenticatedLayout
      breadcrumbs={breadcrumbs}
      pageTitle={t('Manage Calendar')}
      pageActions={
        <div className="flex items-center gap-2">
          <Card className="p-3">
            <RadioGroup
              value={calendarType}
              onValueChange={(value) => {
                setCalendarType(value);
                handleFilterChange('calendar_type', value);
              }}
              className="flex items-center gap-4"
            >
              <div className="flex items-center space-x-2">
                <RadioGroupItem value="local" id="local" />
                <Label htmlFor="local">{t('Calendar')}</Label>
              </div>
              {hasGoogleCalendarConfig && (
                <div className="flex items-center space-x-2">
                  <RadioGroupItem value="google" id="google" />
                  <Label htmlFor="google">{t('Google Calendar')}</Label>
                </div>
              )}
            </RadioGroup>
          </Card>
          <Select
            value={moduleFilter}
            onValueChange={(value) => {
              setModuleFilter(value);
              handleFilterChange('module_filter', value);
            }}
          >
            <SelectTrigger className="w-48">
              <SelectValue placeholder={t('Select Filter')} />
            </SelectTrigger>
            <SelectContent>
              <SelectItem value="all">{t('All')}</SelectItem>
              {Object.entries(availableFilters).map(([key, label]) => (
                <SelectItem key={key} value={key}>{label}</SelectItem>
              ))}
            </SelectContent>
          </Select>
        </div>
      }
    >
      <Head title={t('Calendar')} />

      <Card className="shadow-sm">
        <div className="w-full p-6">
          <CalendarView events={events} />
        </div>
      </Card>
    </AuthenticatedLayout>
  );
}
