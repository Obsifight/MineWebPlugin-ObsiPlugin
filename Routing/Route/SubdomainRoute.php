<?php
class SubdomainRoute extends CakeRoute {
dqs
    public function match ($params) {
        $subdomain = isset($params['subdomain']) ? $params['subdomain'] : null;
        unset($params['subdomain']);
        $path = parent::match($params);
        if ($subdomain) {
            $path = 'http://' . $subdomain . '.obsifight.net' . $path;
        }
        return $path;
    }
}
