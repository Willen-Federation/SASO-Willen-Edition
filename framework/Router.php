<?php
namespace saso\framework;

final class Router
{
    public function __construct(
        private array $route,
    )
    {
    }
    public function route(UserCompiler $input): Loader
    {
        //リクエストがなければstart/start/
        $request0 = $input->request()[0]??'start';
        $request1 = $input->request()[1]??'start';
        $query = $input->query();

        $routeExists = array_key_exists($request0, $this->route)
            && array_key_exists($request1, $this->route[$request0]);

        //matter, action 決定
        if(
            $routeExists
            && ($input->authed() || in_array($request0, ['js', 'installer', 'auth']))
        ) {
            $aRoute = $this->route[$request0][$request1];
            $matter = $aRoute['matter'];
            $action = $aRoute['action'];
            if($matter === 'js') {
                $query['action'] = $action;
            }
        } else if(!$routeExists) {
            $matter = 'error';
            $action = 'notFound';
        } else {
            $matter = 'auth';
            $action = 'start';
            $query['restoredPath'] = array_reduce(
                array_keys($query),
                fn($carry, $item)=>$carry.$item.'/'.$query[$item].'/',
                $request0.'/'.$request1.'/',
            );
        }
        return new Loader(
            $matter,
            $action,
            $query,
            $input->post(),
            $input->config(),
            $input->authed(),
            $input->now(),
        );
    }
}