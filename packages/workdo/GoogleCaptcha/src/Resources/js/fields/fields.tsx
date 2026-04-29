import React, { useEffect } from 'react';
import { getAdminSetting } from '@/utils/helpers';
import ReCAPTCHA from 'react-google-recaptcha';

const SCRIPT_ID = 'recaptcha-v3-script';
const AUTH_PATHS = ['/login', '/register', '/forgot-password'];

const isAuthPage = () => AUTH_PATHS.some(path => window.location.pathname.includes(path));

const cleanupRecaptcha = () => {
  const script = document.getElementById(SCRIPT_ID);
  if (script) script.remove();
  
  document.querySelector('.grecaptcha-badge')?.remove();
  document.querySelectorAll('iframe[src*="recaptcha"], div[style*="recaptcha"]').forEach(el => el.remove());
  
  if ((window as any).grecaptcha) delete (window as any).grecaptcha;
};

const loadRecaptchaV3 = (siteKey: string, setData: any) => {
  if (!isAuthPage()) return cleanupRecaptcha();
  
  let script = document.getElementById(SCRIPT_ID) as HTMLScriptElement;
  
  const executeRecaptcha = () => {
    (window as any).grecaptcha.ready(() => {
      (window as any).grecaptcha.execute(siteKey, { action: 'auth' }).then((token: string) => {
        setData('recaptcha_token', token);
      });
    });
  };

  (window as any).refreshRecaptchaV3 = executeRecaptcha;
  
  if (!script) {
    script = document.createElement('script');
    script.id = SCRIPT_ID;
    script.src = `https://www.google.com/recaptcha/api.js?render=${siteKey}`;
    script.onload = executeRecaptcha;
    document.head.appendChild(script);
  } else if ((window as any).grecaptcha) {
    executeRecaptcha();
  }
};

const createRecaptchaField = (data: any, setData: any, errors: any, t?: any) => {
  const recaptchaEnabled = getAdminSetting('recaptcha_enabled');
  const recaptchaVersion = getAdminSetting('recaptcha_version');
  const recaptchaSiteKey = getAdminSetting('recaptcha_site_key');

  if (recaptchaEnabled !== 'on' || !recaptchaSiteKey) return [];

  (window as any).cleanupRecaptcha = cleanupRecaptcha;

  useEffect(() => {
    if (recaptchaVersion === 'v3') {
      loadRecaptchaV3(recaptchaSiteKey, setData);
    }
    return cleanupRecaptcha;
  }, [recaptchaVersion, recaptchaSiteKey, setData]);

  useEffect(() => {
    const handleNavigation = () => {
      if (!isAuthPage() && (window as any).cleanupRecaptcha) {
        (window as any).cleanupRecaptcha();
      }
    };
    window.addEventListener('popstate', handleNavigation);
    return () => window.removeEventListener('popstate', handleNavigation);
  }, []);

  return [
    {
      id: 'recaptcha-field',
      order: 100,
      component: (
        <div key="recaptcha-field" className="space-y-2">
          {recaptchaVersion === 'v2' && (
            <ReCAPTCHA
              sitekey={recaptchaSiteKey}
              onChange={(value) => setData('recaptcha_token', value)}
              onExpired={() => setData('recaptcha_token', null)}
              onError={() => setData('recaptcha_token', null)}
            />
          )}
          {recaptchaVersion === 'v3' && (
            <div className="text-sm text-gray-600">
              {t('This site is protected by reCAPTCHA and the Google') || 'This site is protected by reCAPTCHA and the Google'}{' '}
              <a href="https://policies.google.com/privacy" target="_blank" rel="noopener noreferrer" className="text-blue-600 hover:underline">
                {t('Privacy Policy') || 'Privacy Policy'}
              </a>{' '}
              {t('and') || 'and'}{' '}
              <a href="https://policies.google.com/terms" target="_blank" rel="noopener noreferrer" className="text-blue-600 hover:underline">
                {t('Terms of Service') || 'Terms of Service'}
              </a>{' '}
              {t('apply.') || 'apply.'}
            </div>
          )}
          {errors.recaptcha_token && (
            <div className="text-sm text-red-600">
              {errors.recaptcha_token}
            </div>
          )}
        </div>
      ),
    },
  ];
};

export const getReCaptchFields = (data: any, setData: any, errors: any, mode: string = 'create', t?: any) => {
  return createRecaptchaField(data, setData, errors, t);
};