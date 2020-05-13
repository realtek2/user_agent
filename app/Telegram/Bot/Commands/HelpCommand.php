<?php


namespace App\Telegram\Bot\Commands;

use Telegram\Bot\Actions;

class HelpCommand extends Command
{
    /**
     * @var string Command Name
     */
    protected $name = 'help';

    /**
     * @var array Command Aliases
     */
    #protected $aliases = [ 'listcommands' ];

    /**
     * @var string Command Description
     */
    protected $description = 'Помошь по работе с ботом, список комманд';

    /**
     * {@inheritdoc}
     */
    public function handle()
    {
        $this->replyWithChatAction([ 'action' => Actions::TYPING ]);

        # Создаём или получаем пользователя и обновляем его последнюю команду
        $this->getTelegramUserFromChat($this->name);

        $commands = $this->telegram->getCommands();

        $text = '';
        foreach ($commands as $name => $handler) {
            /* @var Command $handler */
            $text .= sprintf('/%s - %s'.PHP_EOL, $name, $handler->getDescription());
        }

        $this->replyWithMessage(compact('text'));
    }
}