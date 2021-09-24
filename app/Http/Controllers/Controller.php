<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;

class Controller extends BaseController
{
    /**
     * @OA\Info(
     *      version="1.0.0",
     *      title="Diwa",
     *      description="Diwa is an unofficial simple API from [distrowatch.com](https://distrowatch.com/)",
     *      @OA\Contact(
     *          email="mzulfahmi807@gmail.com"
     *      ),
     *      @OA\License(
     *          name="MIT",
     *          url="https://opensource.org/licenses/MIT"
     *      )
     * )
     */
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;
}
