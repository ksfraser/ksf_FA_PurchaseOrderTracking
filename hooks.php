<?php
declare(strict_types=1);

define('SS_ksf_FA_PurchaseOrderTracking', 149 << 8);

class hooks_ksf_FA_PurchaseOrderTracking extends hooks
{
    var $module_name = 'ksf_FA_PurchaseOrderTracking';
    var $version = '2.4.19-1.0.0';

    function install_extension($check_only=true)
    {
        if (!$check_only) {
            $this->_ensureComposerDependencies();
        }
        return true;
    }

    function activate_extension($company, $check_only=true)
    {
        if ($check_only) {
            return true;
        }

        $autoload = __DIR__ . '/vendor/autoload.php';
        if (file_exists($autoload)) {
            require_once $autoload;
        }

        $sqlFile = __DIR__ . '/sql/install.sql';
        if (file_exists($sqlFile)) {
            $sql = file_get_contents($sqlFile);
            $sql = str_replace('0_', get_company_preference($company)['_prefix'], $sql);
            run_db_import($sql, $company);
        }

        add_security_section(SS_ksf_FA_PurchaseOrderTracking, 'Purchase Order Tracking', 'SA_INVENTORY');
        return true;
    }

    function deactivate_extension($company, $check_only=true)
    {
        if ($check_only) {
            return true;
        }

        $uninstallFile = __DIR__ . '/sql/uninstall.sql';
        if (file_exists($uninstallFile)) {
            $sql = file_get_contents($uninstallFile);
            run_db_import($sql, $company);
        }

        remove_security_section(SS_ksf_FA_PurchaseOrderTracking);
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

    /**
     * Cron hook: nightly_recalc - recalculate lead time stats and fill rates.
     *
     * @param array &$data
     *
     * @since 1.0.0
     */
    function nightly_recalc(array &$data)
    {
        $handler = $this->getHandler();
        $handler->performNightlyRecalculation();

        $data['po_tracking_data'] = $handler->getCachedMetrics();
        $data['po_tracking_processed'] = true;

        hook_invoke_all('po_tracking_data', $data);
    }

    /**
     * Listen for po_tracking_data from other modules.
     *
     * @param array &$data
     *
     * @since 1.0.0
     */
    function po_tracking_data(array &$data)
    {
        if (isset($data['consumers']) && is_array($data['consumers'])) {
            $data['consumers'][] = 'ksf_FA_PurchaseOrderTracking';
        } else {
            $data['consumers'] = ['ksf_FA_PurchaseOrderTracking'];
        }
    }

    /**
     * Listen for grn_received hook (from receiving module or manual entry).
     *
     * @param array &$data {
     *     @var string $po_number
     *     @var int $supplier_id
     *     @var array $lines
     *     @var string $received_date
     * }
     *
     * @since 1.0.0
     */
    function grn_received(array &$data): void
    {
        $handler = $this->getHandler();
        $handler->recordGrnReceipt($data);
    }

    /**
     * Listen for po_created hook.
     *
     * @param array &$data
     *
     * @since 1.0.0
     */
    function po_created(array &$data): void
    {
        $handler = $this->getHandler();
        $handler->recordPoCreation($data);
    }

    /**
     * Listen for po_modified hook.
     *
     * @param array &$data
     *
     * @since 1.0.0
     */
    function po_modified(array &$data): void
    {
        $handler = $this->getHandler();
        $handler->recordPoModification($data);
    }

    private function getHandler(): \Ksfraser\FrontAccounting\PurchaseOrderTracking\PurchaseOrderTrackingHandler
    {
        static $handler = null;

        if ($handler !== null) {
            return $handler;
        }

        $db = new \ksfraser\CommonDb\Adapter\FaDbAdapter(TB_PREF);
        $repository = new \Ksfraser\FrontAccounting\PurchaseOrderTracking\PurchaseOrderRepository($db);
        $handler = new \Ksfraser\FrontAccounting\PurchaseOrderTracking\PurchaseOrderTrackingHandler($repository);

        return $handler;
    }

    private function _ensureComposerDependencies(): void
    {
        $composerDepsPath = dirname(__DIR__) . '/ksf_FA_Common/src/Utils/ComposerDependencies.php';
        if (file_exists($composerDepsPath)) {
            require_once $composerDepsPath;
            \ksfraser\FrontAccounting\Common\Utils\ComposerDependencies::ensure(__DIR__);
        }
    }

    function hook_invoke_all($hook, &$data)
    {
        $autoload = __DIR__ . '/vendor/autoload.php';
        if (!file_exists($autoload)) {
            return null;
        }
        require_once $autoload;

        return parent::hook_invoke_all($hook, $data);
    }
}