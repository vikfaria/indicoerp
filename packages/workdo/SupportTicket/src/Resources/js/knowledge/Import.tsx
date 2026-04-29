import React, { useState } from 'react';
import { useForm } from '@inertiajs/react';
import { Card, CardHeader, CardBody } from '@/Components/ui/card';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { useTranslation } from 'react-i18next';

interface ImportKnowledgeProps {
    routes: {
        import: string;
        index: string;
    };
}

export default function ImportKnowledge({ routes }: ImportKnowledgeProps) {
    const { t } = useTranslation();
    const { data, setData, post, processing, errors } = useForm({
        file: null as File | null,
    });

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        post(routes.import);
    };

    return (
        <div className="row">
            <div className="col-md-12">
                <Card>
                    <CardHeader>
                        <h5>{t('Import Knowledge Base')}</h5>
                    </CardHeader>
                    <CardBody>
                        <form onSubmit={handleSubmit} encType="multipart/form-data">
                            <div className="form-group">
                                <Label htmlFor="file">{t('Select CSV File')}</Label>
                                <Input
                                    type="file"
                                    name="file"
                                    accept=".csv"
                                    required
                                    onChange={(e) => setData('file', e.target.files?.[0] || null)}
                                />
                                <small className="form-text text-muted">
                                    {t('Please upload CSV file with columns: title, description, category')}
                                </small>
                                {errors.file && <div className="text-danger">{errors.file}</div>}
                            </div>
                            <div className="form-group">
                                <Button type="submit" className="btn btn-primary" disabled={processing}>
                                    {processing ? t('Importing...') : t('Import')}
                                </Button>
                                <Button 
                                    type="button" 
                                    className="btn btn-secondary ms-2"
                                    onClick={() => window.location.href = routes.index}
                                >
                                    {t('Cancel')}
                                </Button>
                            </div>
                        </form>
                    </CardBody>
                </Card>
            </div>
        </div>
    );
}