<?php

namespace Webkul\Admin\Http\Controllers\Reporting;

use Webkul\Admin\DataGrids\Reporting\AbandonedReminderReportDataGrid;
use Webkul\Admin\Http\Controllers\Controller;

class AbandonedReminderReportController extends Controller
{
    public function index()
    {
        if (request()->ajax()) {
            return datagrid(AbandonedReminderReportDataGrid::class)->process();
        }

        return view('admin::reporting.abandoned-reminders.index');
    }
}

