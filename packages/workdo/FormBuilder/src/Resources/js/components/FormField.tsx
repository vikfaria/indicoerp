import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Checkbox } from '@/components/ui/checkbox';
import { RadioGroup, RadioGroupItem } from '@/components/ui/radio-group';
import { DatePicker } from '@/components/ui/date-picker';
import { PhoneInputComponent } from '@/components/ui/phone-input';
import InputError from '@/components/ui/input-error';
import { useTranslation } from 'react-i18next';

interface FormFieldProps {
  field: {
    id: number;
    label: string;
    type: string;
    required: boolean;
    placeholder?: string;
    options?: string[];
  };
  value: any;
  onChange: (value: any) => void;
  error?: string;
}

export default function FormField({ field, value, onChange, error }: FormFieldProps) {
  const { t } = useTranslation();
  const fieldId = `field_${field.id}`;

  const renderInput = () => {
    switch (field.type) {
      case 'text':
      case 'email':
      case 'password':
      case 'url':
        return (
          <Input
            type={field.type}
            id={fieldId}
            value={value}
            onChange={(e) => onChange(e.target.value)}
            placeholder={field.placeholder}
            required={field.required}
            className={error ? 'border-red-500' : ''}
          />
        );

      case 'number':
        return (
          <Input
            type="number"
            id={fieldId}
            value={value}
            onChange={(e) => onChange(e.target.value)}
            placeholder={field.placeholder}
            required={field.required}
            min="0"
            step="1"
            className={error ? 'border-red-500' : ''}
          />
        );

      case 'tel':
        return (
          <PhoneInputComponent
            id={fieldId}
            label={field.label}
            value={value}
            onChange={(val) => onChange(val || '')}
            error={error}
            required={field.required}
          />
        );

      case 'date':
        return (
          <DatePicker
            value={value}
            onChange={onChange}
            placeholder={field.placeholder || t('Select date')}
            required={field.required}
          />
        );

      case 'time':
        return (
          <Input
            type="time"
            id={fieldId}
            value={value}
            onChange={(e) => onChange(e.target.value)}
            required={field.required}
            className={error ? 'border-red-500' : ''}
          />
        );

      case 'textarea':
        return (
          <Textarea
            id={fieldId}
            value={value}
            onChange={(e) => onChange(e.target.value)}
            placeholder={field.placeholder}
            required={field.required}
            rows={4}
            className={error ? 'border-red-500' : ''}
          />
        );

      case 'select':
        return (
          <Select value={value} onValueChange={onChange}>
            <SelectTrigger className={error ? 'border-red-500' : ''}>
              <SelectValue placeholder={field.placeholder || t('Select an option')} />
            </SelectTrigger>
            <SelectContent>
              {field.options?.map((option, index) => (
                <SelectItem key={index} value={option}>
                  {option}
                </SelectItem>
              ))}
            </SelectContent>
          </Select>
        );

      case 'checkbox':
        return (
          <div className="flex items-center space-x-2">
            <Checkbox
              id={fieldId}
              checked={value}
              onCheckedChange={onChange}
              required={field.required}
            />
            <Label htmlFor={fieldId} className="text-sm font-medium leading-none peer-disabled:cursor-not-allowed peer-disabled:opacity-70">
              {field.label}
              {field.required && <span className="text-red-500 ml-1">*</span>}
            </Label>
          </div>
        );

      case 'radio':
        return (
          <RadioGroup value={value} onValueChange={onChange} required={field.required}>
            <div className="space-y-2">
              {field.options?.map((option, index) => (
                <div key={index} className="flex items-center space-x-2">
                  <RadioGroupItem value={option} id={`${fieldId}_${index}`} required={field.required} />
                  <Label htmlFor={`${fieldId}_${index}`} className="text-sm font-medium leading-none peer-disabled:cursor-not-allowed peer-disabled:opacity-70">
                    {option}
                  </Label>
                </div>
              ))}
            </div>
          </RadioGroup>
        );

      default:
        return null;
    }
  };

  return (
    <div className="space-y-2">
      {field.type !== 'checkbox' && field.type !== 'tel' && (
        <Label htmlFor={fieldId}
          className="text-sm font-medium text-gray-700"
          {...((field.type === 'select' || field.type === 'date' || field.type === 'radio') && field.required && { required: true })}
        >
          {field.label}
        </Label>
      )}
      {renderInput()
      }
      <InputError message={error} />
    </div>
  );
}