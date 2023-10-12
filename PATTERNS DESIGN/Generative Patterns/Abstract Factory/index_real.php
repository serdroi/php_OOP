<?php

namespace RefactoringGuru\AbstractFactory\RealWorld;

/**
 * Интерфейс Абстрактной фабрики объявляет создающие методы для каждого
 * определённого типа продукта.
 */
interface TemplateFactory
{
    public function createTitleTemplate(): TitleTemplate;

    public function createPageTemplate(): PageTemplate;

    public function getRenderer(): TemplateRenderer;
}

/**
 * Каждая Конкретная Фабрика соответствует определённому варианту (или
 * семейству) продуктов.
 *
 * Эта Конкретная Фабрика создает шаблоны Twig.
 */
class TwigTemplateFactory implements TemplateFactory
{
    public function createTitleTemplate(): TitleTemplate
    {
        return new TwigTitleTemplate();
    }

    public function createPageTemplate(): PageTemplate
    {
        return new TwigPageTemplate($this->createTitleTemplate());
    }

    public function getRenderer(): TemplateRenderer
    {
        return new TwigRenderer();
    }
}

/**
 * А эта Конкретная Фабрика создает шаблоны PHPTemplate.
 */
class PHPTemplateFactory implements TemplateFactory
{
    public function createTitleTemplate(): TitleTemplate
    {
        return new PHPTemplateTitleTemplate();
    }

    public function createPageTemplate(): PageTemplate
    {
        return new PHPTemplatePageTemplate($this->createTitleTemplate());
    }

    public function getRenderer(): TemplateRenderer
    {
        return new PHPTemplateRenderer();
    }
}

/**
 * Каждый отдельный тип продукта должен иметь отдельный интерфейс. Все варианты
 * продукта должны соответствовать одному интерфейсу.
 *
 * Например, этот интерфейс Абстрактного Продукта описывает поведение шаблонов
 * заголовков страниц.
 */
interface TitleTemplate
{
    public function getTemplateString(): string;
}

/**
 * Этот Конкретный Продукт предоставляет шаблоны заголовков страниц Twig.
 */
class TwigTitleTemplate implements TitleTemplate
{
    public function getTemplateString(): string
    {
        return "<h1>{{ title }}</h1>";
    }
}

/**
 * А этот Конкретный Продукт предоставляет шаблоны заголовков страниц
 * PHPTemplate.
 */
class PHPTemplateTitleTemplate implements TitleTemplate
{
    public function getTemplateString(): string
    {
        return "<h1><?= \$title; ?></h1>";
    }
}

/**
 * Это еще один тип Абстрактного Продукта, который описывает шаблоны целых
 * страниц.
 */
interface PageTemplate
{
    public function getTemplateString(): string;
}

/**
 * Шаблон страниц использует под-шаблон заголовков, поэтому мы должны
 * предоставить способ установить объект для этого под-шаблона. Абстрактная
 * фабрика позаботится о том, чтобы подать сюда под-шаблон подходящего типа.
 */
abstract class BasePageTemplate implements PageTemplate
{
    protected $titleTemplate;

    public function __construct(TitleTemplate $titleTemplate)
    {
        $this->titleTemplate = $titleTemplate;
    }
}

/**
 * Вариант шаблонов страниц Twig.
 */
class TwigPageTemplate extends BasePageTemplate
{
    public function getTemplateString(): string
    {
        $renderedTitle = $this->titleTemplate->getTemplateString();

        return <<<HTML
        <div class="page">
            $renderedTitle
            <article class="content">{{ content }}</article>
        </div>
        HTML;
    }
}

/**
 * Вариант шаблонов страниц PHPTemplate.
 */
class PHPTemplatePageTemplate extends BasePageTemplate
{
    public function getTemplateString(): string
    {
        $renderedTitle = $this->titleTemplate->getTemplateString();

        return <<<HTML
        <div class="page">
            $renderedTitle
            <article class="content"><?= \$content; ?></article>
        </div>
        HTML;
    }
}

/**
 * Классы отрисовки отвечают за преобразовании строк шаблонов в конечный HTML
 * код. Каждый такой класс устроен по-раному и ожидает на входе шаблоны только
 * своего типа. Работа с шаблонами через фабрику позволяет вам избавиться от
 * риска подать в отрисовщик шаблон не того типа.
 */
interface TemplateRenderer
{
    public function render(string $templateString, array $arguments = []): string;
}

/**
 * Отрисовщик шаблонов Twig.
 */
class TwigRenderer implements TemplateRenderer
{
    public function render(string $templateString, array $arguments = []): string
    {
        return \Twig::render($templateString, $arguments);
    }
}

/**
 * Отрисовщик шаблонов PHPTemplate. Оговорюсь, что эта реализация очень простая,
 * если не примитивная. В реальных проектах используйте `eval` с
 * осмотрительностью, т.к. неправильное использование этой функции может
 * привести к дырам безопасности.
 */
class PHPTemplateRenderer implements TemplateRenderer
{
    public function render(string $templateString, array $arguments = []): string
    {
        extract($arguments);

        ob_start();
        eval(' ?>' . $templateString . '<?php ');
        $result = ob_get_contents();
        ob_end_clean();

        return $result;
    }
}

/**
 * Клиентский код. Обратите внимание, что он принимает класс Абстрактной Фабрики
 * в качестве параметра, что позволяет клиенту работать с любым типом конкретной
 * фабрики.
 */
class Page
{

    public $title;

    public $content;

    public function __construct($title, $content)
    {
        $this->title = $title;
        $this->content = $content;
    }

    // Вот как вы бы использовали этот шаблон в дальнейшем. Обратите внимание,
    // что класс страницы не зависит ни от классов шаблонов, ни от классов
    // отрисовки.
    public function render(TemplateFactory $factory): string
    {
        $pageTemplate = $factory->createPageTemplate();

        $renderer = $factory->getRenderer();

        return $renderer->render($pageTemplate->getTemplateString(), [
            'title' => $this->title,
            'content' => $this->content
        ]);
    }
}

/**
 * Теперь в других частях приложения клиентский код может принимать фабричные
 * объекты любого типа.
 */
$page = new Page('Sample page', 'This is the body.');

echo "Testing actual rendering with the PHPTemplate factory:\n";
echo $page->render(new PHPTemplateFactory());


// Можете убрать комментарии, если у вас установлен Twig.

// echo "Testing rendering with the Twig factory:\n"; echo $page->render(new
// TwigTemplateFactory());
