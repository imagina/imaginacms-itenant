<?php

namespace Modules\Itenant\Events\Handlers;

class SetMaintenanceMode
{

  private $log = "Itenant: Events|Handler|SendMaintenanceMode|";
  public $notificationService;

  public function __construct()
  {
    $this->notificationService = app("Modules\Notification\Services\Inotification");
  }

  public function handle($event)
  {
    try {

      $isCreated = false;

      //When create
      if (isset($event->organization)) {
        $model = $event->organization;
        $isCreated = true;
      } else {
        //when update
        $params = $event->params;
        //$extraData = $params['extraData'];

        $model = $params['model'];
      }


      //\Log::info('Isite: Events|Handlers|SetMaintenanceMode|Organization:'.$model->id);

      if ($model->enable == 0) {
        //$model->putDownForMaintenance();
        //\Log::info('Itenant: Events|Handlers|SetMaintenanceMode| SET MAINTENANCE: ON');
      } else {
        //$model->update(['maintenance_mode' => null]);
        //\Log::info('Itenant: Events|Handlers|SetMaintenanceMode| SET MAINTENANCE: OFF');
      }
    } catch (\Exception $e) {
      \Log::info($e->getMessage() . ' ' . $e->getFile() . ' ' . $e->getLine());
    }
  }
  
}
