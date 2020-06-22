<?php

namespace App\Listeners;

use App\Tools\MTCloud;
use App\Models\LiveChild;
use Log;

class LiveListener
{
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     *
     * @param  \App\Events\ExampleEvent  $event
     * @return void
     */
    public function handler($cmd, $params)
    {
        if($cmd === 'live.start'){
            Log::info('直播开始:'.json_encode($params));
            $live = LiveChild::where(['course_id' => $params['course_id']])->first();
            $live->status = 2;
            $live->save();

            $response = [
                'code'=> MTCloud::CODE_SUCCESS,
                'data'=> [
                    'role'=> MTCloud::ROLE_ADMIN
                ],
            ];
        }else if($cmd === 'live.stop'){
            Log::info('直播结束:'.json_encode($params));
            $live = LiveChild::where(['course_id' => $params['course_id']])->first();
            $live->status = 3;
            $live->save();
            $response = [
                'code'=> MTCloud::CODE_SUCCESS,
                'data'=> [
                    'role'=> MTCloud::ROLE_ADMIN
                ],
            ];
        }else if($cmd === 'live.playback'){
            Log::info('直播回放生成:'.json_encode($params));
            $live = LiveChild::where(['course_id' => $params['course_id']])->first();
            $live->playback = 1;
            $live->playbackUrl = $params['url'];
            $live->save();
            $response = [
                'code'=> MTCloud::CODE_SUCCESS,
                'data'=> [
                    'role'=> MTCloud::ROLE_ADMIN
                ],
            ];
        }else if($cmd === 'user.login'){
            $response = [
                'code'=> MTCloud::CODE_SUCCESS,
                'data'=> [
                    'role'=> MTCloud::ROLE_ADMIN
                ],
            ];
        }


        return $response;
    }
}
