<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\ActivityLogModel;

class Aktivitas extends BaseController
{
    private const PER_PAGE = 50;

    public function index()
    {
        $this->global['pageTitle'] = 'Log Aktivitas';

        $filters = [
            'q'         => trim((string) $this->request->getGet('q')),
            'action'    => (string) $this->request->getGet('action'),
            'module'    => (string) $this->request->getGet('module'),
            'record_id' => (string) $this->request->getGet('record_id'),
            'user_name' => (string) $this->request->getGet('user_name'),
            'dari'      => $this->validDate($this->request->getGet('dari')),
            'sampai'    => $this->validDate($this->request->getGet('sampai')),
        ];
        if (! isset(ActivityLogModel::MODULES[$filters['module']])) {
            $filters['module'] = '';
        }
        if (! isset(ActivityLogModel::ACTIONS[$filters['action']])) {
            $filters['action'] = '';
        }
        if (! ctype_digit($filters['record_id'])) {
            $filters['record_id'] = '';
        }

        $model = new ActivityLogModel();

        $targetLabel = null;
        if ($filters['record_id'] !== '') {
            $latest = $model->where('record_id', (int) $filters['record_id'])
                ->where('id_rt', current_rt_id())
                ->orderBy('id', 'DESC')
                ->first();
            if ($latest && ! empty($latest['record_label'])) {
                $targetLabel = $latest['record_label'];
            }
        }

        $data['logs']        = $model->forCurrentRt($filters)->paginate(self::PER_PAGE);
        $data['pager']       = $model->pager;
        $data['filters']     = $filters;
        $data['userNames']   = $model->userNames();
        $data['stats']       = $model->getStats();
        $data['targetLabel'] = $targetLabel;

        return $this->loadViews('admin/aktivitas', $this->global, $data);
    }

    private function validDate($value): string
    {
        $value = (string) $value;

        return preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) && strtotime($value) ? $value : '';
    }
}
