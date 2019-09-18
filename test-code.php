<?php
        if($source === 'dmkt-sp.jp') {
            // https:// dev5 or dev-final or 本番 /ticket/customer?source=dmkt-sp.jp&tptn= のみアクセス可
            $env = config('app.env');
            if($env === 'local' || $env === 'develop') {
                $redirect_uri = "https://dev5.airtrip.jp/ticket/customer?source=". $source . "&tptn=";
            } elseif($env === 'production') {
                $redirect_uri = config('api.'.config('app.env').'.url.airtrip') . "ticket/customer?source=". $source . "&tptn=";
            }

            $d_point = new DPointService();
            $auth_code = $request->code; // 認可コード
            $state = $request->state;

            if((!empty($auth_code) && !empty($state)) || Session::has('account_id')) {
                $d_user_data = $this->getDpointUserData($d_point, $auth_code, $state, $redirect_uri);

                if(!empty($d_user_data) || !empty($d_user_data['accountid']) || !Session::has('account_id')) {
                    // アカウント識別子をsession, cookieに保存
                    Session::put('account_id', $d_user_data['accountid']['account_id']);
                    Cookie::queue('account_id', $d_user_data['accountid']['account_id']);

                    $viewData['d_user']          = $d_user_data['d_user'];
                    $viewData['accountid']       = $d_user_data['accountid'];
                    $viewData['merumaga_status'] = $d_user_data['merumaga_status'];
                    $viewData['authcode_param']  = http_build_query(['code' => $auth_code, 'state' => $state]);
                } else {
                    $viewData['accountid'] = '';
                }
            } else {
                // ログインURL生成API
                $login_url = $d_point->getLoginUrl($redirect_uri);

                if(empty($login_url) || !empty($login_url['status'])) {
                    $viewData['dlogin_url'] = null;
                } else {
                    $viewData['dlogin_url'] = $login_url;
                }
                $viewData['d_user'] = null;
            }
            // $request->session()->forget('account_id');
            // setcookie('account_id');
        }