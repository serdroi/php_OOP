<?php

namespace RefactoringGuru\Composite\RealWorld;

/**
 * Базовый класс Компонент объявляет интерфейс для всех конкретных компонентов,
 * как простых, так и сложных.
 *
 * В нашем примере мы сосредоточимся на поведении рендеринга элементов DOM.
 */
abstract class FormElement
{
    /**
     * Можно предположить, что всем элементам DOM будут нужны эти 3 поля.
     */
    protected $name;
    protected $title;
    protected $data;

    public function __construct(string $name, string $title)
    {
        $this->name = $name;
        $this->title = $title;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setData($data): void
    {
        $this->data = $data;
    }

    public function getData(): array
    {
        return $this->data;
    }

    /**
     * Каждый конкретный элемент DOM должен предоставить свою реализацию
     * рендеринга, но мы можем с уверенностью предположить, что все они будут
     * возвращать строки.
     */
    abstract public function render(): string;
}

/**
 * Это компонент-Лист. Как и все Листья, он не может иметь вложенных
 * компонентов.
 */
class Input extends FormElement
{
    private $type;

    public function __construct(string $name, string $title, string $type)
    {
        parent::__construct($name, $title);
        $this->type = $type;
    }

    /**
     * Поскольку у компонентов-Листьев нет вложенных компонентов, которые могут
     * выполнять за них основную часть работы, обычно Листья делают большую
     * часть тяжёлой работы внутри паттерна Компоновщик.
     */
    public function render(): string
    {
        return "<label for=\"{$this->name}\">{$this->title}</label>\n" .
            "<input name=\"{$this->name}\" type=\"{$this->type}\" value=\"{$this->data}\">\n";
    }
}

/**
 * Базовый класс Контейнер реализует инфраструктуру для управления дочерними
 * объектами, повторно используемую всеми Конкретными Контейнерами.
 */
abstract class FieldComposite extends FormElement
{
    /**
     * @var FormElement[]
     */
    protected $fields = [];

    /**
     * Методы добавления/удаления подобъектов.
     */
    public function add(FormElement $field): void
    {
        $name = $field->getName();
        $this->fields[$name] = $field;
    }

    public function remove(FormElement $component): void
    {
        $this->fields = array_filter($this->fields, function ($child) use ($component) {
            return $child != $component;
        });
    }

    /**
     * В то время как метод Листа просто выполняет эту работу, метод Контейнера
     * почти всегда должен учитывать его подобъекты.
     *
     * В этом случае контейнер может принимать структурированные данные.
     *
     * @param array $data
     */
    public function setData($data): void
    {
        foreach ($this->fields as $name => $field) {
            if (isset($data[$name])) {
                $field->setData($data[$name]);
            }
        }
    }

    /**
     * Та же логика применима и к получателю. Он возвращает структурированные
     * данные самого контейнера (если они есть), а также все дочерние данные.
     */
    public function getData(): array
    {
        $data = [];
        
        foreach ($this->fields as $name => $field) {
            $data[$name] = $field->getData();
        }
        
        return $data;
    }

    /**
     * Базовая реализация рендеринга Контейнера просто объединяет результаты
     * всех дочерних элементов. Конкретные Контейнеры смогут повторно
     * использовать эту реализацию в своих реальных реализациях рендеринга.
     */
    public function render(): string
    {
        $output = "";
        
        foreach ($this->fields as $name => $field) {
            $output .= $field->render();
        }
        
        return $output;
    }
}

/**
 * Элемент fieldset представляет собой Конкретный Контейнер.
 */
class Fieldset extends FieldComposite
{
    public function render(): string
    {
        // Обратите внимание, как комбинированный результат рендеринга потомков
        // включается в тег fieldset.
        $output = parent::render();
        
        return "<fieldset><legend>{$this->title}</legend>\n$output</fieldset>\n";
    }
}

/**
 * Так же как и элемент формы.
 */
class Form extends FieldComposite
{
    protected $url;

    public function __construct(string $name, string $title, string $url)
    {
        parent::__construct($name, $title);
        $this->url = $url;
    }

    public function render(): string
    {
        $output = parent::render();
        return "<form action=\"{$this->url}\">\n<h3>{$this->title}</h3>\n$output</form>\n";
    }
}

/**
 * Клиентский код получает удобный интерфейс для построения сложных древовидных
 * структур.
 */
function getProductForm(): FormElement
{
    $form = new Form('product', "Add product", "/product/add");
    $form->add(new Input('name', "Name", 'text'));
    $form->add(new Input('description', "Description", 'text'));

    $picture = new Fieldset('photo', "Product photo");
    $picture->add(new Input('caption', "Caption", 'text'));
    $picture->add(new Input('image', "Image", 'file'));
    $form->add($picture);

    return $form;
}

/**
 * Структура формы может быть заполнена данными из разных источников. Клиент не
 * должен проходить через все поля формы, чтобы назначить данные различным
 * полям, так как форма сама может справиться с этим.
 */
function loadProductData(FormElement $form)
{
    $data = [
        'name' => 'Apple MacBook',
        'description' => 'A decent laptop.',
        'photo' => [
            'caption' => 'Front photo.',
            'image' => 'photo1.png',
        ],
    ];

    $form->setData($data);
}

/**
 * Клиентский код может работать с элементами формы, используя абстрактный
 * интерфейс. Таким образом, не имеет значения, работает ли клиент с простым
 * компонентом или сложным составным деревом.
 */
function renderProduct(FormElement $form)
{
    // ..

    echo $form->render();

    // ..
}

$form = getProductForm();
loadProductData($form);
renderProduct($form);
