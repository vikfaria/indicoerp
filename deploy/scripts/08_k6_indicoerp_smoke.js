import http from 'k6/http';
import { check, group, sleep } from 'k6';

const baseUrl = (__ENV.K6_BASE_URL || 'https://indicoerp.com').replace(/\/+$/, '');
const loginEmail = __ENV.K6_LOGIN_EMAIL || '';
const loginPassword = __ENV.K6_LOGIN_PASSWORD || '';
const thinkTimeSeconds = Number(__ENV.K6_THINK_TIME_SECONDS || 1);
const authPaths = (__ENV.K6_AUTH_PATHS || '/dashboard')
    .split(',')
    .map((path) => path.trim())
    .filter(Boolean);

export const options = {
    vus: Number(__ENV.K6_VUS || 10),
    duration: __ENV.K6_DURATION || '2m',
    thresholds: {
        http_req_failed: ['rate<0.02'],
        http_req_duration: ['p(95)<1500'],
        checks: ['rate>0.99'],
    },
};

function extractCsrfToken(html) {
    const match = html.match(/name="_token"\s+value="([^"]+)"/);
    return match ? match[1] : null;
}

function getCommonHeaders(referer) {
    return {
        Referer: referer,
        'User-Agent': 'k6-indicoerp-smoke/1.0',
    };
}

function runGuestJourney() {
    group('guest', () => {
        const home = http.get(`${baseUrl}/`, {
            headers: getCommonHeaders(`${baseUrl}/`),
            tags: { journey: 'guest', step: 'home' },
        });

        check(home, {
            'guest home ok': (response) => [200, 301, 302].includes(response.status),
        });

        const login = http.get(`${baseUrl}/login`, {
            headers: getCommonHeaders(`${baseUrl}/login`),
            tags: { journey: 'guest', step: 'login' },
        });

        check(login, {
            'guest login page ok': (response) => response.status === 200,
        });
    });
}

function runAuthenticatedJourney() {
    if (!loginEmail || !loginPassword) {
        return;
    }

    group('authenticated', () => {
        const loginPage = http.get(`${baseUrl}/login`, {
            headers: getCommonHeaders(`${baseUrl}/login`),
            tags: { journey: 'auth', step: 'login-page' },
        });

        const csrfToken = extractCsrfToken(loginPage.body || '');
        check(loginPage, {
            'auth login page ok': (response) => response.status === 200,
            'csrf token found': () => Boolean(csrfToken),
        });

        if (!csrfToken) {
            return;
        }

        const payload = {
            _token: csrfToken,
            email: loginEmail,
            password: loginPassword,
        };

        const loginResponse = http.post(`${baseUrl}/login`, payload, {
            headers: {
                ...getCommonHeaders(`${baseUrl}/login`),
            },
            redirects: 0,
            tags: { journey: 'auth', step: 'login-submit' },
        });

        check(loginResponse, {
            'auth login submit ok': (response) => [302, 303].includes(response.status),
        });

        for (const path of authPaths) {
            const targetPath = path.startsWith('/') ? path : `/${path}`;
            const response = http.get(`${baseUrl}${targetPath}`, {
                headers: getCommonHeaders(`${baseUrl}${targetPath}`),
                tags: { journey: 'auth', step: targetPath },
            });

            check(response, {
                [`auth path ok ${targetPath}`]: (currentResponse) => currentResponse.status === 200,
            });
        }
    });
}

export default function () {
    runGuestJourney();
    runAuthenticatedJourney();
    sleep(thinkTimeSeconds);
}
