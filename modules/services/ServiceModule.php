<?php
declare(strict_types=1);

class ServiceModule
{
    public static function activeCategoriesWithServices(): array
    {
        $cats = Database::fetchAll("SELECT * FROM categories WHERE status = 'active' ORDER BY sort_order ASC, id ASC");
        $services = Database::fetchAll("SELECT * FROM services WHERE status = 'active' ORDER BY sort_order ASC, id ASC");
        $byCat = [];
        foreach ($services as $svc) {
            $byCat[(int)$svc['category_id']][] = $svc;
        }
        foreach ($cats as &$cat) {
            $cat['services'] = $byCat[(int)$cat['id']] ?? [];
        }
        return $cats;
    }
}
