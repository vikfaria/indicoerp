<?php

namespace Workdo\LandingPage\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Inertia\Inertia;
use Workdo\LandingPage\Models\NewsletterSubscriber;
use Workdo\LandingPage\Http\Requests\StoreNewsletterSubscriberRequest;
use Workdo\LandingPage\Events\CreateNewsletterSubscriber;
use Workdo\LandingPage\Events\DestroyNewsletterSubscriber;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;

class NewsletterSubscriberController extends Controller
{
    public function index(Request $request)
    {
        if(Auth::user()->can('manage-newsletter-subscribers')){
            $subscribers = NewsletterSubscriber::query()
                ->when($request->filled('email'), fn($q) => $q->where('email', 'like', '%' . $request->email . '%'))
                ->when($request->filled('sort'), fn($q) => $q->orderBy($request->sort, $request->get('direction', 'asc')), fn($q) => $q->latest('subscribed_at'))
                ->paginate($request->get('per_page', 10))
                ->withQueryString();

            return Inertia::render('LandingPage/NewsletterSubscribers/Index', [
                'subscribers' => $subscribers,
                'filters' => $request->only(['email', 'sort', 'direction'])
            ]);
        }
        else{
            return back()->with('error', __('Permission denied'));
        }
    }

    public function store(StoreNewsletterSubscriberRequest $request)
    {
        $validated = $request->validated();
        
        // Get IP and location info
        $ip = $request->ip();
        $userAgent = $request->userAgent();
        
        // Get location from IP (you can use a service like ipapi.co)
        $locationData = $this->getLocationFromIP($ip);
        
        // Parse user agent for browser and device info
        $deviceInfo = $this->parseUserAgent($userAgent);
        
        $subscriber = new NewsletterSubscriber();
        $subscriber->email = $validated['email'];
        $subscriber->subscribed_at = now();
        $subscriber->ip_address = $ip;
        $subscriber->country = $locationData['country'] ?? null;
        $subscriber->city = $locationData['city'] ?? null;
        $subscriber->region = $locationData['region'] ?? null;
        $subscriber->country_code = $locationData['country_code'] ?? null;
        $subscriber->isp = $locationData['isp'] ?? null;
        $subscriber->org = $locationData['org'] ?? null;
        $subscriber->timezone = $locationData['timezone'] ?? null;
        $subscriber->latitude = $locationData['latitude'] ?? null;
        $subscriber->longitude = $locationData['longitude'] ?? null;
        $subscriber->user_agent = $userAgent;
        $subscriber->browser = $deviceInfo['browser_name'] ?? null;
        $subscriber->os = $deviceInfo['os_name'] ?? null;
        $subscriber->device = $deviceInfo['device_type'] ?? null;
        $subscriber->save();

        // Dispatch event for packages to handle their fields
        CreateNewsletterSubscriber::dispatch($request, $subscriber);

        return response()->json([
            'success' => true,
            'message' => __('Thank you for subscribing to our newsletter!')
        ]);
    }
    
    private function getLocationFromIP($ip)
    {
        try {
            // Skip for local IPs
            if ($ip === '127.0.0.1' || $ip === '::1' || strpos($ip, '192.168.') === 0 || strpos($ip, '10.') === 0) {
                return [
                    'country' => 'Local',
                    'city' => 'Local',
                    'isp' => 'Local Network',
                    'org' => 'Local',
                    'timezone' => 'Local',
                    'region' => 'Local'
                ];
            }
            
            $response = Http::timeout(5)->get("http://ip-api.com/json/{$ip}");
            if ($response->successful()) {
                $data = $response->json();
                return [
                    'country' => $data['country'] ?? null,
                    'city' => $data['city'] ?? null,
                    'isp' => $data['isp'] ?? null,
                    'org' => $data['org'] ?? null,
                    'timezone' => $data['timezone'] ?? null,
                    'region' => $data['regionName'] ?? null,
                    'country_code' => $data['countryCode'] ?? null,
                    'latitude' => $data['lat'] ?? null,
                    'longitude' => $data['lon'] ?? null
                ];
            }
        } catch (\Exception $e) {
            // Log error but don't fail the subscribers
            \Log::warning('Failed to get location from IP: ' . $e->getMessage());
        }
        
        return [
            'country' => null,
            'city' => null,
            'isp' => null,
            'org' => null,
            'timezone' => null,
            'region' => null
        ];
    }
    
    private function parseUserAgent($userAgent)
    {
        // Use the same parseBrowserData function as AuthenticatedSessionController
        return parseBrowserData($userAgent);
    }

    public function destroy(NewsletterSubscriber $subscriber)
    {
        if(Auth::user()->can('delete-newsletter-subscribers')){
            DestroyNewsletterSubscriber::dispatch($subscriber);
            
            $subscriber->delete();

            return back()->with('success', __('The Newsletter subscriber has been deleted.'));
        }
        else{
            return back()->with('error', __('Permission denied'));
        }
    }

    public function export(Request $request)
    {
        if(Auth::user()->can('export-newsletter-subscribers')){
            $subscribers = NewsletterSubscriber::query()
                ->when($request->filled('email'), fn($q) => $q->where('email', 'like', '%' . $request->email . '%'))
                ->orderBy('subscribed_at', 'desc')
                ->get();

            $filename = 'newsletter_subscribers_' . now()->format('Y_m_d_H_i_s') . '.csv';
            
            $headers = [
                'Content-Type' => 'text/csv',
                'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            ];

            $callback = function() use ($subscribers) {
                $file = fopen('php://output', 'w');
                fputcsv($file, ['Email', 'Subscribed At', 'IP Address', 'Country', 'City', 'Region', 'ISP', 'Organization', 'Timezone', 'Browser', 'OS', 'Device']);

                foreach ($subscribers as $subscriber) {
                    fputcsv($file, [
                        $subscriber->email,
                        $subscriber->subscribed_at->format('Y-m-d H:i:s'),
                        $subscriber->ip_address ?? '',
                        $subscriber->country ?? '',
                        $subscriber->city ?? '',
                        $subscriber->region ?? '',
                        $subscriber->isp ?? '',
                        $subscriber->org ?? '',
                        $subscriber->timezone ?? '',
                        $subscriber->browser ?? '',
                        $subscriber->os ?? '',
                        $subscriber->device ?? '',
                    ]);
                }

                fclose($file);
            };

            return response()->stream($callback, 200, $headers);
        }
        else{
            return back()->with('error', __('Permission denied'));
        }
    }
}