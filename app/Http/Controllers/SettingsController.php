<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use App\Models\TermsAndCondition;
use App\Models\SystemNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SettingsController extends Controller
{
    /**
     * Display settings page
     */
    public function index()
    {
        if (!auth()->user()->hasAnyPermission(['settings.view', 'settings.read'])) {
            abort(403, 'Unauthorized action. You do not have permission to view ERP Settings.');
        }

        $settings = Setting::getAllGrouped();
        
        $termsAndConditions = TermsAndCondition::ordered()->get();
        return view('admin_panel.settings.index', compact('settings', 'termsAndConditions'));
    }

    /**
     * Update settings
     */
    public function update(Request $request)
    {
        if (!auth()->user()->hasAnyPermission(['settings.edit', 'settings.update'])) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized action. You do not have permission to edit ERP Settings.',
            ], 403);
        }

        $request->validate([
            'settings' => 'nullable|array',
            'company_logo' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg,webp|max:3072',
            'remove_company_logo' => 'nullable|boolean',
        ]);

        // 1. Process regular key-value settings
        if ($request->has('settings') && is_array($request->settings)) {
            foreach ($request->settings as $key => $value) {
                Setting::set($key, $value);

                // Synchronize with SaleFieldConfiguration if applicable
                if (in_array($key, ['summary_customer_balance', 'print_tax_breakdown', 'inv_show_disc_column'])) {
                    $boolVal = (bool) $value;
                    $fieldKey = $key === 'inv_show_disc_column' ? 'col_discount' : $key;
                    $fieldConfig = \App\Models\SaleFieldConfiguration::where('field_key', $fieldKey)->first();
                    if ($fieldConfig) {
                        $fieldConfig->is_enabled = $boolVal;
                        $fieldConfig->visible_in_print = $boolVal;
                        $fieldConfig->save();
                    }
                }
            }
            \App\Models\SaleFieldConfiguration::clearCache();
        }

        // 2. Handle Company Logo Upload & Removal
        if ($request->hasFile('company_logo')) {
            $file = $request->file('company_logo');
            $fileName = 'logo_' . time() . '.' . $file->getClientOriginalExtension();

            $publicUploadDir = public_path('uploads/settings');
            $rootUploadDir = base_path('uploads/settings');

            if (!file_exists($publicUploadDir)) {
                @mkdir($publicUploadDir, 0775, true);
            }
            if (!file_exists($rootUploadDir)) {
                @mkdir($rootUploadDir, 0775, true);
            }

            $file->move($publicUploadDir, $fileName);
            $logoPath = 'uploads/settings/' . $fileName;

            // Ensure available in root uploads for direct web access on live servers
            if (file_exists($publicUploadDir . '/' . $fileName) && $publicUploadDir !== $rootUploadDir) {
                @copy($publicUploadDir . '/' . $fileName, $rootUploadDir . '/' . $fileName);
            }

            Setting::set('company_logo', $logoPath, 'company', 'Company Logo', 'string');
        } elseif ($request->boolean('remove_company_logo')) {
            Setting::set('company_logo', null, 'company', 'Company Logo', 'string');
        }

        $currentLogo = Setting::get('company_logo');
        $hasLogo = !empty($currentLogo) && (file_exists(public_path($currentLogo)) || file_exists(base_path($currentLogo)));

        return response()->json([
            'success' => true,
            'message' => 'Settings updated successfully',
            'logo_url' => $hasLogo ? asset($currentLogo) : null,
        ]);
    }

    /**
     * Display return policy settings page
     */
    public function returnSettings()
    {
        if (!auth()->user()->hasAnyPermission(['settings.view', 'settings.read'])) {
            abort(403, 'Unauthorized action. You do not have permission to view Return Policy Settings.');
        }

        $settings = \App\Models\SystemSetting::where('group', 'returns')->get();
        
        return view('admin_panel.settings.return_policy', compact('settings'));
    }

    /**
     * Update return policy settings
     */
    public function updateReturnSettings(Request $request)
    {
        if (!auth()->user()->hasAnyPermission(['settings.edit', 'settings.update'])) {
            abort(403, 'Unauthorized action. You do not have permission to edit Return Policy Settings.');
        }

        $validated = $request->validate([
            'return_deadline_days' => 'required|integer|min:0|max:365',
            'return_require_approval' => 'nullable|boolean',
            'return_auto_approve_threshold' => 'nullable|numeric|min:0',
        ]);

        foreach ($validated as $key => $value) {
            \App\Models\SystemSetting::set($key, $value);
        }

        return redirect()->back()->with('success', 'Return policy settings updated successfully!');
    }

    /**
     * Show return approvers management page
     */
    public function returnApprovers()
    {
        if (!auth()->user()->hasAnyPermission(['settings.view', 'settings.read'])) {
            abort(403, 'Unauthorized action. You do not have permission to view Return Approvers Settings.');
        }

        $users = \App\Models\User::with('roles')
            ->where('id', '!=', auth()->id()) // Exclude current user
            ->orderBy('name')
            ->get();
        
        return view('admin_panel.settings.return_approvers', compact('users'));
    }

    /**
     * Update return approval permissions for users
     */
    public function updateReturnApprovers(Request $request)
    {
        if (!auth()->user()->hasAnyPermission(['settings.edit', 'settings.update'])) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized action. You do not have permission to edit Return Approvers Settings.',
            ], 403);
        }

        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'can_approve_returns' => 'nullable|boolean',
            'can_approve_past_deadline_returns' => 'nullable|boolean',
        ]);

        $user = \App\Models\User::findOrFail($validated['user_id']);
        
        $user->can_approve_returns = $request->has('can_approve_returns');
        $user->can_approve_past_deadline_returns = $request->has('can_approve_past_deadline_returns');
        $user->save();

        return response()->json([
            'success' => true,
            'message' => "Permissions updated for {$user->name}",
        ]);
    }

    /**
     * Get notifications for current user
     */
    public function notifications()
    {
        $notifications = SystemNotification::where('user_id', Auth::id())
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return view('admin_panel.notifications.index', compact('notifications'));
    }

    /**
     * Get unread notification count
     */
    public function notificationCount()
    {
        $count = SystemNotification::getUnreadCount(Auth::id());
        
        return response()->json(['count' => $count]);
    }

    /**
     * Mark notification as read
     */
    public function markAsRead($id)
    {
        $notification = SystemNotification::where('user_id', Auth::id())
            ->findOrFail($id);
        
        $notification->markAsRead();

        return response()->json(['success' => true]);
    }

    /**
     * Mark all notifications as read
     */
    public function markAllAsRead()
    {
        SystemNotification::where('user_id', Auth::id())
            ->where('is_read', false)
            ->update([
                'is_read' => true,
                'read_at' => now(),
            ]);

        return response()->json(['success' => true]);
    }

    /**
     * Store a new terms and condition (ERP Settings)
     */
    public function storeTerm(Request $request)
    {
        if (!auth()->user()->hasAnyPermission(['settings.edit', 'settings.update'])) {
            return response()->json(['success' => false, 'message' => 'Unauthorized action.'], 403);
        }

        $validated = $request->validate([
            'heading' => 'required|string|max:255',
            'paragraph' => 'required|string',
            'sort_order' => 'nullable|integer|min:0',
        ]);

        $validated['sort_order'] = $validated['sort_order'] ?? (TermsAndCondition::max('sort_order') + 1);

        $term = TermsAndCondition::create($validated);

        \Illuminate\Support\Facades\Cache::forget('api_terms_and_conditions');

        return response()->json([
            'success' => true,
            'message' => 'Terms & Condition added successfully.',
            'term' => $term
        ]);
    }

    /**
     * Update an existing terms and condition (ERP Settings)
     */
    public function updateTerm(Request $request, $id)
    {
        if (!auth()->user()->hasAnyPermission(['settings.edit', 'settings.update'])) {
            return response()->json(['success' => false, 'message' => 'Unauthorized action.'], 403);
        }

        $validated = $request->validate([
            'heading' => 'required|string|max:255',
            'paragraph' => 'required|string',
            'sort_order' => 'nullable|integer|min:0',
        ]);

        $term = TermsAndCondition::findOrFail($id);
        $term->update($validated);

        \Illuminate\Support\Facades\Cache::forget('api_terms_and_conditions');

        return response()->json([
            'success' => true,
            'message' => 'Terms & Condition updated successfully.',
            'term' => $term
        ]);
    }

    /**
     * Delete a terms and condition (ERP Settings)
     */
    public function deleteTerm($id)
    {
        if (!auth()->user()->hasAnyPermission(['settings.edit', 'settings.update'])) {
            return response()->json(['success' => false, 'message' => 'Unauthorized action.'], 403);
        }

        $term = TermsAndCondition::findOrFail($id);
        $term->delete();

        \Illuminate\Support\Facades\Cache::forget('api_terms_and_conditions');

        return response()->json([
            'success' => true,
            'message' => 'Terms & Condition deleted successfully.'
        ]);
    }

    /**
     * Toggle active status of a terms and condition (ERP Settings)
     */
    public function toggleTerm($id)
    {
        if (!auth()->user()->hasAnyPermission(['settings.edit', 'settings.update'])) {
            return response()->json(['success' => false, 'message' => 'Unauthorized action.'], 403);
        }

        $term = TermsAndCondition::findOrFail($id);
        $term->is_active = !$term->is_active;
        $term->save();

        \Illuminate\Support\Facades\Cache::forget('api_terms_and_conditions');

        return response()->json([
            'success' => true,
            'message' => 'Status updated successfully.',
            'is_active' => $term->is_active
        ]);
    }
}
