<?php
declare(strict_types=1);

define('SS_ksf_FA_PurchaseOrderTracking', 149 << 8);

if (file_exists(__DIR__ . '/vendor/autoload.php')) {
    require_once __DIR__ . '/vendor/autoload.php';
}

$composerDepsPath = __DIR__ . '/vendor/ksfraser/ksf-common-db/src/Utils/ComposerDependencies.php';
if (file_exists($composerDepsPath)) {
    require_once $composerDepsPath;
    \ksfraser\FrontAccounting\Common\Utils\ComposerDependencies::ensure(__DIR__);
}

class hooks_ksf_FA_PurchaseOrderTracking extends hooks
{
    var $module_name = 'ksf_FA_PurchaseOrderTracking';
    var $version = '2.4.19-1.0.0';

    function activate_extension($company, $check_only=true)
    {
        if (!file_exists(dirname(__FILE__) . '/sql/install.sql')) {
            return true;
        }

        $updates = array(
            'install.sql' => array(
                'ksf_po_lead_times',
                'ksf_po_fill_rates',
                'ksf_po_events',
                'ksf_po_tracking_cron',
            ),
        );

        return $this->update_databases($company, $updates, $check_only);
    }

    function deactivate_extension($company, $check_only=true)
    {
        return true;
    }

    function getModuleConstants(&$data, $opts = [])
    {
        $data['constants']['SS_ksf_FA_PurchaseOrderTracking'] = SS_ksf_FA_PurchaseOrderTracking;
        $data['constants']['SA_ksf_FA_POTRACKING'] = SS_ksf_FA_PurchaseOrderTracking | 1;
        $data['constants']['SA_ksf_FA_POTRACKING_VIEW'] = SS_ksf_FA_PurchaseOrderTracking | 2;
        return $data;
    }

    function getModuleCapabilities(&$data, $opts = [])
    {
        $data['capabilities']['po_tracking'] = [
            'view' => 'SA_ksf_FA_POTRACKING_VIEW',
            'manage' => 'SA_ksf_FA_POTRACKING',
        ];
        return $data;
    }

    public function hasCapability(&$data, $opts = null)
    {
        $capability = isset($opts['capability']) ? $opts['capability'] : (isset($data['capability']) ? $data['capability'] : null);
        if ($capability === null) {
            $data['has_capability'] = false;
            return false;
        }
        $caps = ['view', 'manage'];
        $hasCapability = in_array($capability, $caps);
        $data['has_capability'] = $hasCapability;
        return $hasCapability;
    }

    public function respondToCapabilityRequest(&$data, $opts = null)
    {
        $request = isset($opts['request']) ? $opts['request'] : (isset($data['request']) ? $data['request'] : 'capabilities');
        $data['request'] = $request;
        $data['module'] = $this->module_name;

        if (strpos($request, 'has:') === 0) {
            $capability = substr($request, 4);
            return $this->hasCapability($data, ['capability' => $capability]);
        }

        switch ($request) {
            case 'capabilities':
                $data['capabilities'] = $this->getModuleCapabilities($data, $opts);
                return $data['capabilities'];
            default:
                return null;
        }
    }

    function nightly_recalc(array &$data)
    {
        $handler = $this->getHandler();
        $handler->performNightlyRecalculation();

        $data['po_tracking_data'] = $handler->getCachedMetrics();
        $data['po_tracking_processed'] = true;

        hook_invoke_all('po_tracking_data', $data);
    }

    function po_tracking_data(array &$data)
    {
        if (isset($data['consumers']) && is_array($data['consumers'])) {
            $data['consumers'][] = 'ksf_FA_PurchaseOrderTracking';
        } else {
            $data['consumers'] = ['ksf_FA_PurchaseOrderTracking'];
        }
    }

    function grn_received(array &$data): void
    {
        $handler = $this->getHandler();
        $handler->recordGrnReceipt($data);
    }

    function po_created(array &$data): void
    {
        $handler = $this->getHandler();
        $handler->recordPoCreation($data);
    }

    function po_modified(array &$data): void
    {
        $handler = $this->getHandler();
        $handler->recordPoModification($data);
    }

    private function getHandler()
    {
        static $handler = null;

        if ($handler !== null) {
            return $handler;
        }

        if (!class_exists('\Ksfraser\FrontAccounting\PurchaseOrderTracking\PurchaseOrderTrackingHandler')) {
            return null;
        }

        $db = new \ksfraser\CommonDb\Adapter\FaDbAdapter(TB_PREF);
        $repository = new \Ksfraser\FrontAccounting\PurchaseOrderTracking\PurchaseOrderRepository($db);
        $handler = new \Ksfraser\FrontAccounting\PurchaseOrderTracking\PurchaseOrderTrackingHandler($repository);

        return $handler;
    }

    function install_access()
    {
        $security_sections[SS_ksf_FA_PurchaseOrderTracking] = _("Purchase Order Tracking");
        $security_areas['SA_ksf_FA_POTRACKING'] = array(
            SS_ksf_FA_PurchaseOrderTracking | 1,
            _("Manage Purchase Order Tracking")
        );
        $security_areas['SA_ksf_FA_POTRACKING_VIEW'] = array(
            SS_ksf_FA_PurchaseOrderTracking | 2,
            _("View Purchase Order Tracking")
        );
        return array($security_areas, $security_sections);
    }
}