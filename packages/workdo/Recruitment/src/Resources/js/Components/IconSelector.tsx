import { useState } from 'react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Popover, PopoverContent, PopoverTrigger } from '@/components/ui/popover';
import { ScrollArea } from '@/components/ui/scroll-area';
import { Search, Check, FileText, Clock, Mail, Phone, User, Users, Calendar, CheckCircle, AlertCircle, Info, Star, Heart, Bookmark, Flag, Target, Award, Briefcase, Building, Home, Settings, Bell, Lock, Eye, Download, Upload, Edit, Trash2, Plus, Minus, X, ArrowRight, ArrowLeft, ArrowUp, ArrowDown } from 'lucide-react';
import { cn } from '@/lib/utils';

interface IconSelectorProps {
    value: string;
    onChange: (iconName: string) => void;
    disabled?: boolean;
    className?: string;
}

const availableIcons = [
    { name: 'FileText', icon: FileText },
    { name: 'Clock', icon: Clock },
    { name: 'Mail', icon: Mail },
    { name: 'Phone', icon: Phone },
    { name: 'User', icon: User },
    { name: 'Users', icon: Users },
    { name: 'Calendar', icon: Calendar },
    { name: 'CheckCircle', icon: CheckCircle },
    { name: 'AlertCircle', icon: AlertCircle },
    { name: 'Info', icon: Info },
    { name: 'Star', icon: Star },
    { name: 'Heart', icon: Heart },
    { name: 'Bookmark', icon: Bookmark },
    { name: 'Flag', icon: Flag },
    { name: 'Target', icon: Target },
    { name: 'Award', icon: Award },
    { name: 'Briefcase', icon: Briefcase },
    { name: 'Building', icon: Building },
    { name: 'Home', icon: Home },
    { name: 'Settings', icon: Settings },
    { name: 'Bell', icon: Bell },
    { name: 'Lock', icon: Lock },
    { name: 'Eye', icon: Eye },
    { name: 'Download', icon: Download },
    { name: 'Upload', icon: Upload },
    { name: 'Edit', icon: Edit },
    { name: 'Trash2', icon: Trash2 },
    { name: 'Plus', icon: Plus },
    { name: 'Minus', icon: Minus },
    { name: 'X', icon: X },
    { name: 'ArrowRight', icon: ArrowRight },
    { name: 'ArrowLeft', icon: ArrowLeft },
    { name: 'ArrowUp', icon: ArrowUp },
    { name: 'ArrowDown', icon: ArrowDown },
];

export default function IconSelector({ value, onChange, disabled = false, className }: IconSelectorProps) {
    const [open, setOpen] = useState(false);
    const [searchTerm, setSearchTerm] = useState('');

    const filteredIcons = availableIcons.filter(icon =>
        icon.name.toLowerCase().includes(searchTerm.toLowerCase())
    );

    const selectedIcon = availableIcons.find(icon => icon.name === value);
    const SelectedIconComponent = selectedIcon?.icon;

    const handleIconSelect = (iconName: string) => {
        onChange(iconName);
        setOpen(false);
    };

    return (
        <Popover open={open} onOpenChange={setOpen}>
            <PopoverTrigger asChild>
                <Button
                    variant="outline"
                    role="combobox"
                    aria-expanded={open}
                    className={cn("w-full justify-between", className)}
                    disabled={disabled}
                >
                    <div className="flex items-center gap-2">
                        {SelectedIconComponent && <SelectedIconComponent className="h-4 w-4" />}
                        <span>{value || "Select icon..."}</span>
                    </div>
                    <Search className="ml-2 h-4 w-4 shrink-0 opacity-50" />
                </Button>
            </PopoverTrigger>
            <PopoverContent className="w-80 p-0">
                <div className="p-3 border-b">
                    <Input
                        placeholder="Search icons..."
                        value={searchTerm}
                        onChange={(e) => setSearchTerm(e.target.value)}
                        className="h-8"
                    />
                </div>
                <ScrollArea className="h-64">
                    <div className="grid grid-cols-4 gap-1 p-2">
                        {filteredIcons.map((icon) => {
                            const IconComponent = icon.icon;
                            const isSelected = value === icon.name;
                            
                            return (
                                <Button
                                    key={icon.name}
                                    variant="ghost"
                                    size="sm"
                                    className={cn(
                                        "h-12 w-full flex flex-col items-center justify-center gap-1 relative",
                                        isSelected && "bg-accent"
                                    )}
                                    onClick={() => handleIconSelect(icon.name)}
                                >
                                    <IconComponent className="h-4 w-4" />
                                    <span className="text-xs truncate w-full">{icon.name}</span>
                                    {isSelected && (
                                        <Check className="absolute top-1 right-1 h-3 w-3" />
                                    )}
                                </Button>
                            );
                        })}
                    </div>
                </ScrollArea>
            </PopoverContent>
        </Popover>
    );
}