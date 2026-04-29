import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Label } from '@/components/ui/label';
import { Input } from '@/components/ui/input';
import { Switch } from '@/components/ui/switch';
import { useTranslation } from 'react-i18next';

interface ColorsProps {
    data: any;
    getSectionData: (key: string) => any;
    updateSectionData: (key: string, updates: any) => void;
    updateSectionVisibility: (key: string, visible: boolean) => void;
    setData: (key: string, value: any) => void;
}

export default function Colors({ data, getSectionData, updateSectionData, updateSectionVisibility, setData }: ColorsProps) {
    const { t } = useTranslation();
    
    const colors = data.config_sections?.colors || {
        primary: '#10b77f',
        secondary: '#059669', 
        accent: '#065f46'
    };

    const updateColors = (colorKey: string, value: string) => {
        const updatedColors = { ...colors, [colorKey]: value };
        setData('config_sections', {
            ...data.config_sections,
            colors: updatedColors
        });
    };

    const presetColors = [
        { name: 'Green', primary: '#10b77f', secondary: '#059669', accent: '#065f46' },
        { name: 'Blue', primary: '#3b82f6', secondary: '#1d4ed8', accent: '#1e3a8a' },
        { name: 'Purple', primary: '#8b5cf6', secondary: '#7c3aed', accent: '#581c87' },
        { name: 'Orange', primary: '#f97316', secondary: '#ea580c', accent: '#9a3412' },
        { name: 'Red', primary: '#ef4444', secondary: '#dc2626', accent: '#991b1b' }
    ];

    return (
        <Card>
            <CardHeader>
                <div className="flex items-center justify-between">
                    <CardTitle>{t('Color Settings')}</CardTitle>
                </div>
            </CardHeader>
            <CardContent className="space-y-6">
                <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <Label htmlFor="primary-color">{t('Primary Color')}</Label>
                        <div className="flex gap-2 mt-1">
                            <Input
                                id="primary-color"
                                type="color"
                                value={colors.primary}
                                onChange={(e) => updateColors('primary', e.target.value)}
                                className="w-16 h-10 p-1 border rounded"
                            />
                            <Input
                                type="text"
                                value={colors.primary}
                                onChange={(e) => updateColors('primary', e.target.value)}
                                className="flex-1"
                                placeholder="#3b82f6"
                            />
                        </div>
                    </div>

                    <div>
                        <Label htmlFor="secondary-color">{t('Secondary Color')}</Label>
                        <div className="flex gap-2 mt-1">
                            <Input
                                id="secondary-color"
                                type="color"
                                value={colors.secondary}
                                onChange={(e) => updateColors('secondary', e.target.value)}
                                className="w-16 h-10 p-1 border rounded"
                            />
                            <Input
                                type="text"
                                value={colors.secondary}
                                onChange={(e) => updateColors('secondary', e.target.value)}
                                className="flex-1"
                                placeholder="#8b5cf6"
                            />
                        </div>
                    </div>

                    <div>
                        <Label htmlFor="accent-color">{t('Accent Color')}</Label>
                        <div className="flex gap-2 mt-1">
                            <Input
                                id="accent-color"
                                type="color"
                                value={colors.accent}
                                onChange={(e) => updateColors('accent', e.target.value)}
                                className="w-16 h-10 p-1 border rounded"
                            />
                            <Input
                                type="text"
                                value={colors.accent}
                                onChange={(e) => updateColors('accent', e.target.value)}
                                className="flex-1"
                                placeholder="#10b77f"
                            />
                        </div>
                    </div>
                </div>

                <div>
                    <Label>{t('Color Presets')}</Label>
                    <div className="grid grid-cols-5 gap-3 mt-3">
                        {presetColors.map((preset) => {
                            const isActive = colors.primary === preset.primary && colors.secondary === preset.secondary && colors.accent === preset.accent;
                            return (
                                <button
                                    key={preset.name}
                                    onClick={() => {
                                        setData('config_sections', {
                                            ...data.config_sections,
                                            colors: {
                                                primary: preset.primary,
                                                secondary: preset.secondary,
                                                accent: preset.accent
                                            }
                                        });
                                    }}
                                    className={`p-3 border-2 rounded-xl hover:shadow-lg transition-all transform hover:scale-105 ${
                                        isActive ? 'border-blue-500 shadow-lg ring-2 ring-blue-200' : 'border-gray-200 hover:border-gray-300'
                                    }`}
                                    title={preset.name}
                                >
                                    <div className="flex justify-center gap-1 mb-2">
                                        <div 
                                            className="w-6 h-6 rounded-full shadow-sm" 
                                            style={{ backgroundColor: preset.primary }}
                                        ></div>
                                        <div 
                                            className="w-6 h-6 rounded-full shadow-sm" 
                                            style={{ backgroundColor: preset.secondary }}
                                        ></div>
                                        <div 
                                            className="w-6 h-6 rounded-full shadow-sm" 
                                            style={{ backgroundColor: preset.accent }}
                                        ></div>
                                    </div>
                                    <div className={`text-xs font-medium ${
                                        isActive ? 'text-blue-600' : 'text-gray-600'
                                    }`}>{preset.name}</div>
                                    {isActive && (
                                        <div className="w-2 h-2 bg-blue-500 rounded-full mx-auto mt-1"></div>
                                    )}
                                </button>
                            );
                        })}
                    </div>
                </div>
            </CardContent>
        </Card>
    );
}