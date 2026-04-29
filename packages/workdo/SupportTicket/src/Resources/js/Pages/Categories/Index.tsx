import { useState } from 'react';
import { Head, Link } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
import AuthenticatedLayout from '@/layouts/authenticated-layout';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { DataTable } from '@/components/ui/data-table';
import { Badge } from '@/components/ui/badge';
import { Plus, Edit, Trash2 } from 'lucide-react';

interface Category {
  id: number;
  name: string;
  color: string;
  created_at: string;
}

export default function Index() {
  const { t } = useTranslation();
  const [categories] = useState<Category[]>([]);

  const tableColumns = [
    {
      key: 'name',
      header: t('Name'),
      sortable: true
    },
    {
      key: 'color',
      header: t('Color'),
      render: (value: string, row: Category) => (
        <Badge style={{ backgroundColor: row.color }}>
          {row.color}
        </Badge>
      )
    },
    {
      key: 'created_at',
      header: t('Created'),
      render: (value: string) => new Date(value).toLocaleDateString()
    },
    {
      key: 'actions',
      header: t('Actions'),
      render: (_: any, category: Category) => (
        <div className="flex gap-1">
          <Button variant="ghost" size="sm" asChild>
            <Link href={`/ticket-categories/${category.id}/edit`}>
              <Edit className="h-4 w-4" />
            </Link>
          </Button>
          <Button variant="ghost" size="sm" className="text-destructive">
            <Trash2 className="h-4 w-4" />
          </Button>
        </div>
      )
    }
  ];

  return (
    <AuthenticatedLayout
      breadcrumbs={[
        {label: t('Support Tickets')},
        {label: t('Categories')}
      ]}
      pageTitle={t('Manage Categories')}
      pageActions={
        <Button size="sm" asChild>
          <Link href="/ticket-categories/create">
            <Plus className="h-4 w-4" />
          </Link>
        </Button>
      }
    >
      <Head title={t('Ticket Categories')} />
      
      <Card>
        <CardContent className="p-0">
          <DataTable
            data={categories}
            columns={tableColumns}
            className="rounded-none"
          />
        </CardContent>
      </Card>
    </AuthenticatedLayout>
  );
}