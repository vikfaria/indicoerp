<?php

namespace Workdo\AuthorizeNet\Http\Controllers;

use App\Http\Controllers\Controller;
use Workdo\AuthorizeNet\Http\Requests\UpdateAuthorizeNetSettingsRequest;
use Workdo\AuthorizeNet\Services\AuthorizeNetService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;

class AuthorizeNetSettingsController extends Controller
{
    public function update(UpdateAuthorizeNetSettingsRequest $request)
    {
        if (Auth::user()->can('edit-authorizenet-settings')) {
            $validated = $request->validated();

            $settings = $validated['settings'];
            try {
                foreach ($settings as $key => $value) {
                    setSetting($key, $value, creatorId(), $key == "authorizenet_enabled");
                }

                return redirect()->back()->with('success', __('AuthorizeNet settings save successfully.'));
            } catch (\Exception $e) {
                return redirect()->back()->with('error', __('Failed to update AuthorizeNet settings: ') . $e->getMessage());
            }
        }
        return back()->with('error', __('Permission denied'));
    }

    public function processPayment(Request $request)
    {
        try {
            if ($request->has('redirect_route')) {
                $authorizeNetService = new AuthorizeNetService($request->get('config', []));
                $response = $authorizeNetService->createPayment($request->all());
                $request->offsetUnset('config');
                $success = $response->messages->resultCode == 'Ok' && isset($response->transactionResponse->responseCode) && $response->transactionResponse->responseCode == '1';
                $data = $request->merge(['success' => $success])->toArray();
                return redirect()->route($request->input('redirect_route'), $data);
            }
            throw new \Exception('Redirect URL not provided');
        } catch (\Exception $e) {
            $userSlug = $request->route('userSlug') ? ['userSlug' => $request->route('userSlug')] : [];
            return redirect()->route($request->input('back_route'), $request['userSlug'] ?? $userSlug)
                ->with('error', $e->getMessage());
        }
    }
}
