<?php

namespace YG\Mariadbdump\Builder;

use YG\Mariadbdump\Models\View;

class ViewBuilder extends BuilderAbstract
{
    private View $view;

    public function __construct(View $view)
    {
        $this->view = $view;
    }

    public function build(): array
    {
        return [
            'view' => $this->getViewCode(),
        ];
    }

    public function getViewCode(): string
    {
        return 'CREATE VIEW `' . $this->view->name . '` AS ' . $this->view->definition . ';';
    }
}