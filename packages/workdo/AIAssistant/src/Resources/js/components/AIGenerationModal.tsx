import { useState } from 'react';
import axios from 'axios';
import { toast } from 'sonner';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { RadioGroup, RadioGroupItem } from '@/components/ui/radio-group';
import { Slider } from '@/components/ui/slider';
import { Dialog, DialogContent, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { useTranslation } from 'react-i18next';
import { Sparkles } from 'lucide-react';

interface AIGenerationModalProps {
    isOpen: boolean;
    onClose: () => void;
    fieldType: string;
    fieldLabel: string;
    onGenerate: (results: string[]) => void;
    context?: any;
    module?: string;
    submodule?: string;
}

export const AIGenerationModal = ({
    isOpen,
    onClose,
    fieldType,
    fieldLabel,
    onGenerate,
    context = {},
    module,
    submodule
}: AIGenerationModalProps) => {
    const { t } = useTranslation();
    const [loading, setLoading] = useState(false);
    const [results, setResults] = useState<string[]>([]);

    const [settings, setSettings] = useState({
        language: 'english',
        creativity: 'medium',
        resultCount: 1,
        maxLength: 100,
        fieldContent: ''
    });

    const handleGenerate = async () => {
        setLoading(true);
        try {
            // Module will be passed from the hook context
            const response = await axios.post(route('ai-assistant.generate'), {
                fieldType,
                settings,
                context,
                module,
                submodule
            });

            const data = response.data;

            if (data.success) {
                setResults(data.results || []);
                if (data.message) {
                    toast.success(data.message);
                }
            } else {
                // Handle error response
                const errorMessage = data.message || data.error || 'Generation failed';
                toast.error(errorMessage);
            }
        } catch (error: any) {
            const errorMessage = error.response?.data?.message || error.response?.data?.error || 'Network error. Please try again.';
            toast.error(errorMessage);
        } finally {
            setLoading(false);
        }
    };

    const handleSelectResult = (result: string) => {
        onGenerate([result]);
        onClose();
    };

    return (
        <Dialog open={isOpen} onOpenChange={onClose}>
            <DialogContent className="max-w-2xl">
                <DialogHeader>
                    <DialogTitle className="flex items-center gap-2">
                        <Sparkles className="h-5 w-5" />
                        {t('Generate')} {fieldLabel} {t('with AI')}
                    </DialogTitle>
                </DialogHeader>

                <div className="space-y-4">
                    {/* Language Selection */}
                    <div>
                        <Label>{t('Language')}</Label>
                        <Select
                            value={settings.language}
                            onValueChange={(value) => setSettings(prev => ({ ...prev, language: value }))}
                        >
                            <SelectTrigger>
                                <SelectValue />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="english">{t('English')}</SelectItem>
                                <SelectItem value="arabic">{t('Arabic')}</SelectItem>
                                <SelectItem value="danish">{t('Danish')}</SelectItem>
                                <SelectItem value="spanish">{t('Spanish')}</SelectItem>
                                <SelectItem value="french">{t('French')}</SelectItem>
                                <SelectItem value="german">{t('German')}</SelectItem>
                                <SelectItem value="hebrew">{t('Hebrew')}</SelectItem>
                                <SelectItem value="italian">{t('Italian')}</SelectItem>
                                <SelectItem value="japanese">{t('Japanese')}</SelectItem>
                                <SelectItem value="dutch">{t('Dutch')}</SelectItem>
                                <SelectItem value="polish">{t('Polish')}</SelectItem>
                                <SelectItem value="portuguese">{t('Portuguese')}</SelectItem>
                                <SelectItem value="russian">{t('Russian')}</SelectItem>
                                <SelectItem value="turkish">{t('Turkish')}</SelectItem>
                                <SelectItem value="chinese">{t('Chinese')}</SelectItem>
                            </SelectContent>
                        </Select>
                    </div>

                    {/* AI Creativity */}
                    <div>
                        <Label>{t('AI Creativity')}</Label>
                        <RadioGroup
                            value={settings.creativity}
                            onValueChange={(value) => setSettings(prev => ({ ...prev, creativity: value }))}
                            className="flex gap-6 mt-2"
                        >
                            <div className="flex items-center space-x-2">
                                <RadioGroupItem value="low" />
                                <Label>{t('Low')}</Label>
                            </div>
                            <div className="flex items-center space-x-2">
                                <RadioGroupItem value="medium" />
                                <Label>{t('Medium')}</Label>
                            </div>
                            <div className="flex items-center space-x-2">
                                <RadioGroupItem value="high" />
                                <Label>{t('High')}</Label>
                            </div>
                        </RadioGroup>
                    </div>

                    {/* Number of Results */}
                    <div>
                        <Label>{t('Number of Results')}</Label>
                        <Select
                            value={settings.resultCount.toString()}
                            onValueChange={(value) => setSettings(prev => ({ ...prev, resultCount: parseInt(value) }))}
                        >
                            <SelectTrigger>
                                <SelectValue />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="1">1</SelectItem>
                                <SelectItem value="2">2</SelectItem>
                                <SelectItem value="3">3</SelectItem>
                                <SelectItem value="5">5</SelectItem>
                            </SelectContent>
                        </Select>
                    </div>

                    {/* Maximum Length */}
                    <div>
                        <Label>{t('Maximum Length')} ({settings.maxLength} {t('characters')})</Label>
                        <Slider
                            value={[settings.maxLength]}
                            onValueChange={([value]) => setSettings(prev => ({ ...prev, maxLength: value }))}
                            max={500}
                            min={50}
                            step={25}
                            className="mt-2"
                        />
                    </div>

                    {/* Field Content Input */}
                    <div>
                        <Label>{t('Additional Context for')} {fieldLabel}</Label>
                        <Textarea
                            placeholder={t('Provide additional context...')}
                            value={settings.fieldContent}
                            onChange={(e) => setSettings(prev => ({ ...prev, fieldContent: e.target.value }))}
                        />
                    </div>

                    {/* Generate Button */}
                    <Button
                        onClick={handleGenerate}
                        disabled={loading}
                        className="w-full"
                    >
                        {loading ? t('Generating...') : t('Generate Content')}
                    </Button>

                    {/* Results Display */}
                    {results.length > 0 && (
                        <div className="space-y-2">
                            <Label>{t('Generated Results')} ({t('Click to select')}):</Label>
                            {results.map((result, index) => (
                                <div
                                    key={index}
                                    className="p-3 border rounded cursor-pointer hover:bg-gray-50"
                                    onClick={() => handleSelectResult(result)}
                                >
                                    <p className="text-sm">{result}</p>
                                    <span className="text-xs text-gray-500">
                                        {result.length} {t('characters')}
                                    </span>
                                </div>
                            ))}
                        </div>
                    )}
                </div>
            </DialogContent>
        </Dialog>
    );
};
