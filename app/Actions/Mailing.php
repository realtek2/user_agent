<?php

namespace App\Actions;
use TCG\Voyager\Actions\AbstractAction;
use Illuminate\Support\Facades\Log;

class Mailing extends AbstractAction
{
    public function getTitle()
    {
        return 'Разослать';
    }
    public function getIcon()
    {
        return 'voyager-paper-plane';
    }
    public function getPolicy()
    {
        return 'read';
    }
    public function shouldActionDisplayOnDataType()
    {
        return $this->dataType->slug == 'blog-articles';
    }
    public function getAttributes()
    {
        return [
            'class' => 'btn btn-sm btn-primary pull-right',
        ];
    }
    public function getDefaultRoute()
    {
        return route('blog:mailing', ['id' => $this->data->id]);
    }
}