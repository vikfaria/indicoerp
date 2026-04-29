import { useState, useRef } from 'react';
import { useTranslation } from 'react-i18next';
import { router } from '@inertiajs/react';
import { Dialog, DialogContent, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Button } from '@/components/ui/button';
import { PenTool } from 'lucide-react';
import SignatureCanvas from 'react-signature-canvas';

interface SignatureModalProps {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    contractId: number;
}

export default function SignatureModal({ open, onOpenChange, contractId }: SignatureModalProps) {
    const { t } = useTranslation();
    const [isSubmitting, setIsSubmitting] = useState(false);
    const signatureRef = useRef<SignatureCanvas>(null);

    const handleSubmit = () => {
        if (signatureRef.current?.isEmpty()) {
            alert(t('Please draw your signature'));
            return;
        }

        const signatureData = signatureRef.current?.toDataURL() || '';
        setIsSubmitting(true);

        router.post(route('contract-signatures.store', contractId), {
            signature_data: signatureData,
            signature_type: 'drawn',
        }, {
            onSuccess: () => {
                onOpenChange(false);
                signatureRef.current?.clear();
            },
            onFinish: () => setIsSubmitting(false),
        });
    };

    const clearSignature = () => {
        signatureRef.current?.clear();
    };

    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent className="max-w-2xl">
                <DialogHeader>
                    <DialogTitle className="flex items-center gap-2">
                        <PenTool className="h-5 w-5" />
                        {t('Sign Contract')}
                    </DialogTitle>
                </DialogHeader>

                <div className="space-y-4 my-3">
                    <div className="border-2 border-dashed border-gray-300 rounded-lg p-4">
                        <SignatureCanvas
                            ref={signatureRef}
                            canvasProps={{
                                width: 500,
                                height: 200,
                                className: 'signature-canvas w-full h-48 border rounded'
                            }}
                        />
                    </div>
                    <p className="text-sm text-muted-foreground text-center">
                        {t('Draw your signature in the box above')}
                    </p>
                </div>

                <div className="flex justify-between pt-4">
                    <Button
                        type="button"
                        variant="outline"
                        onClick={clearSignature}
                    >
                        {t('Clear')}
                    </Button>
                    <div className="flex gap-2">
                        <Button
                            type="button"
                            variant="outline"
                            onClick={() => onOpenChange(false)}
                        >
                            {t('Cancel')}
                        </Button>
                        <Button
                            type="button"
                            onClick={handleSubmit}
                            disabled={isSubmitting}
                        >
                            {isSubmitting ? t('Signing...') : t('Sign Contract')}
                        </Button>
                    </div>
                </div>
            </DialogContent>
        </Dialog>
    );
}