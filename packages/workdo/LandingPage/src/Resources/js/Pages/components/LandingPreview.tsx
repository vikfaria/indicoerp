import React from 'react';
import { useTranslation } from 'react-i18next';

interface LandingPreviewProps {
  settings?: any;
}

export function LandingPreview({ settings }: LandingPreviewProps) {
  const { t } = useTranslation();
  const getSectionData = (key: string) => {
    return settings?.config_sections?.sections?.[key] || {};
  };
  
  const isSectionVisible = (key: string) => {
    return settings?.config_sections?.section_visibility?.[key] !== false;
  };

  const colors = settings?.config_sections?.colors || {
    primary: '#10b77f',
    secondary: '#059669',
    accent: '#f59e0b'
  };

  const sectionOrder = settings?.config_sections?.section_order || 
    ['header', 'hero', 'stats', 'features', 'modules', 'benefits', 'gallery', 'cta', 'footer'];

  const renderMiniSection = (sectionKey: string) => {
    if (!isSectionVisible(sectionKey)) return null;
    
    const sectionData = getSectionData(sectionKey);
    
    switch (sectionKey) {
      case 'header':
        return (
          <div key={sectionKey} className="flex justify-between items-center p-3 bg-white border-b shadow-sm">
            <div className="text-sm font-bold" style={{ color: colors.primary }}>
              {sectionData.company_name || settings?.company_name || 'ERPGo SaaS'}
            </div>
            <div className="text-xs text-white px-3 py-1 rounded-full shadow-sm transition-colors" style={{ backgroundColor: colors.primary }}>
              {sectionData.cta_text || t('Get Started')}
            </div>
          </div>
        );
        
      case 'hero':
        return (
          <div key={sectionKey} className="p-4 text-white text-center relative overflow-hidden" style={{ background: `linear-gradient(135deg, ${colors.primary}, ${colors.secondary}, ${colors.accent})` }}>
            <div className="absolute inset-0 bg-black/10"></div>
            <div className="relative z-10">
              <div className="text-sm font-bold mb-2 leading-tight">
                {sectionData.title || t('Transform Your Business')}
              </div>
              <div className="text-xs opacity-90 mb-3 leading-relaxed">
                {sectionData.subtitle?.substring(0, 60) || t('Complete business solution')}...
              </div>
              <div className="flex gap-2 justify-center">
                <div className="text-xs bg-white px-3 py-1.5 rounded-full font-medium shadow-lg hover:shadow-xl transition-shadow" style={{ color: colors.primary }}>
                  {sectionData.primary_button_text || t('Start Trial')}
                </div>
                <div className="text-xs border border-white/50 px-3 py-1.5 rounded-full backdrop-blur-sm hover:bg-white/10 transition-colors">
                  {sectionData.secondary_button_text || t('Login')}
                </div>
              </div>
            </div>
          </div>
        );
        
      case 'stats':
        return (
          <div key={sectionKey} className="p-4 text-white" style={{ background: `linear-gradient(to right, ${colors.primary}, ${colors.secondary})` }}>
            <div className="grid grid-cols-2 gap-3 text-center">
              <div className="bg-white/10 rounded-lg p-2 backdrop-blur-sm">
                <div className="text-sm font-bold">{sectionData.businesses || '10K+'}</div>
                <div className="text-xs opacity-90">{t('Businesses')}</div>
              </div>
              <div className="bg-white/10 rounded-lg p-2 backdrop-blur-sm">
                <div className="text-sm font-bold">{sectionData.uptime || '99.9%'}</div>
                <div className="text-xs opacity-90">{t('Uptime')}</div>
              </div>
              <div className="bg-white/10 rounded-lg p-2 backdrop-blur-sm">
                <div className="text-sm font-bold">{sectionData.support || '24/7'}</div>
                <div className="text-xs opacity-90">{t('Support')}</div>
              </div>
              <div className="bg-white/10 rounded-lg p-2 backdrop-blur-sm">
                <div className="text-sm font-bold">{sectionData.countries || '50+'}</div>
                <div className="text-xs opacity-90">{t('Countries')}</div>
              </div>
            </div>
          </div>
        );
        
      case 'features':
        return (
          <div key={sectionKey} className="p-4 bg-gray-50">
            <div className="text-sm font-bold text-center mb-3 text-gray-800">
              {sectionData.title || t('Powerful Features')}
            </div>
            <div className="grid grid-cols-2 gap-2">
              {(sectionData.features || [{}, {}, {}, {}]).slice(0, 4).map((feature: any, i: number) => (
                <div key={i} className="bg-white p-2 rounded-lg shadow-sm border hover:shadow-md transition-shadow">
                  <div className="w-4 h-4 rounded-lg mx-auto mb-1" style={{ background: `linear-gradient(135deg, ${colors.primary}, ${colors.secondary})` }}></div>
                  <div className="text-xs text-center font-medium text-gray-700">{feature.title?.substring(0, 10) || `Feature ${i+1}`}</div>
                </div>
              ))}
            </div>
          </div>
        );
        
      case 'modules':
        return (
          <div key={sectionKey} className="p-2 bg-white">
            <div className="text-xs font-bold text-center mb-1">
              {sectionData.title || t('Business Solutions')}
            </div>
            <div className="flex gap-1 justify-center">
              {['ERP', 'CRM', 'HRM', 'POS'].map((module, i) => (
                <div key={i} className="text-xs bg-gray-100 px-1 py-0.5 rounded">
                  {module}
                </div>
              ))}
            </div>
          </div>
        );
        
      case 'benefits':
        return (
          <div key={sectionKey} className="p-2 bg-gray-50">
            <div className="text-xs font-bold text-center mb-1">
              {sectionData.title || 'Why Choose Us?'}
            </div>
            <div className="space-y-1">
              {Array.from({ length: 3 }).map((_, i) => (
                <div key={i} className="flex items-center gap-1">
                  <div className="w-1 h-1 bg-green-500 rounded-full"></div>
                  <div className="text-xs text-gray-600">{t('Benefit')} {i + 1}</div>
                </div>
              ))}
            </div>
          </div>
        );
        
      case 'gallery':
        return (
          <div key={sectionKey} className="p-2 bg-white">
            <div className="text-xs font-bold text-center mb-1">
              {sectionData.title || 'Gallery'}
            </div>
            <div className="grid grid-cols-3 gap-1">
              {Array.from({ length: 3 }).map((_, i) => (
                <div key={i} className="aspect-square bg-gray-200 rounded"></div>
              ))}
            </div>
          </div>
        );
        
      case 'cta':
        return (
          <div key={sectionKey} className="p-2 text-white text-center" style={{ backgroundColor: colors.primary }}>
            <div className="text-xs font-bold mb-1">
              {sectionData.title || t('Ready to Transform?')}
            </div>
            <div className="flex gap-1 justify-center">
              <div className="text-xs bg-white px-2 py-1 rounded" style={{ color: colors.primary }}>
                {sectionData.primary_button || t('Start Trial')}
              </div>
              <div className="text-xs border border-white px-2 py-1 rounded">
                {sectionData.secondary_button || t('Contact')}
              </div>
            </div>
          </div>
        );
        
      case 'footer':
        return (
          <div key={sectionKey} className="p-4 bg-gradient-to-r from-gray-900 to-gray-800 text-white">
            <div className="grid grid-cols-1 gap-3 text-xs">
              <div className="text-center border-b border-gray-700 pb-2">
                <div className="font-bold text-sm" style={{ color: colors.accent }}>
                  {settings?.company_name || t('ERPGo SaaS')}
                </div>
                <div className="text-gray-400 text-xs mt-1">
                  {sectionData.description?.substring(0, 30) || t('Business solution')}...
                </div>
              </div>
              <div className="flex justify-between text-gray-400">
                <span>{t('Product')}</span>
                <span>{t('Company')}</span>
                <span>{t('Support')}</span>
              </div>
            </div>
          </div>
        );
        
      default:
        return null;
    }
  };

  return (
    <div className="border rounded-lg overflow-hidden bg-white shadow-lg">
      <div className="px-3 py-2 border-b" style={{ background: `linear-gradient(to right, ${colors.primary}, ${colors.secondary})` }}>
        <div className="text-sm font-semibold text-white flex items-center gap-2">
          <div className="w-2 h-2 bg-white rounded-full animate-pulse"></div>
          {t('Live Preview')}
        </div>
      </div>
      <div className="relative">
        <div className="absolute top-2 right-2 z-10">
          <div className="bg-black/20 backdrop-blur-sm rounded-full px-2 py-1">
            <div className="text-xs text-white font-medium">{t('Mobile View')}</div>
          </div>
        </div>
        <div className="max-h-[500px] overflow-y-auto scrollbar-thin scrollbar-thumb-gray-300 scrollbar-track-gray-100">
          <div className="space-y-0">
            {sectionOrder.map(sectionKey => renderMiniSection(sectionKey))}
          </div>
        </div>
      </div>
      <div className="bg-gray-50 px-3 py-2 border-t">
        <div className="flex items-center justify-between text-xs text-gray-500">
          <span>{sectionOrder.filter(key => isSectionVisible(key)).length} {t('sections active')}</span>
          <div className="flex gap-1">
            <div className="w-1 h-1 bg-green-500 rounded-full"></div>
            <span>{t('Live')}</span>
          </div>
        </div>
      </div>
    </div>
  );
}