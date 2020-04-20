<?php

/* traits.twig */
class __TwigTemplate_3e42af870250007127f315c1d3ea67e458d79ee5844a46d93d56ddaeade9af86 extends Twig_Template
{
    private $source;

    public function __construct(Twig_Environment $env)
    {
        parent::__construct($env);

        $this->source = $this->getSourceContext();

        // line 1
        $this->parent = $this->loadTemplate("layout/layout.twig", "traits.twig", 1);
        $this->blocks = array(
            'title' => array($this, 'block_title'),
            'body_class' => array($this, 'block_body_class'),
            'page_content' => array($this, 'block_page_content'),
        );
    }

    protected function doGetParent(array $context)
    {
        return "layout/layout.twig";
    }

    protected function doDisplay(array $context, array $blocks = array())
    {
        // line 2
        $context["__internal_060648be7509ffdc1cd8971040d81681617caedbed26e93d4857f5f46e9abcc4"] = $this->loadTemplate("macros.twig", "traits.twig", 2);
        // line 1
        $this->parent->display($context, array_merge($this->blocks, $blocks));
    }

    // line 3
    public function block_title($context, array $blocks = array())
    {
        echo "Traits | ";
        $this->displayParentBlock("title", $context, $blocks);
    }

    // line 4
    public function block_body_class($context, array $blocks = array())
    {
        echo "traits";
    }

    // line 6
    public function block_page_content($context, array $blocks = array())
    {
        // line 7
        echo "    <div class=\"page-header\">
        <h1>Traits</h1>
    </div>

    <div class=\"container-fluid underlined\">
        ";
        // line 12
        $context['_parent'] = $context;
        $context['_seq'] = twig_ensure_traversable((isset($context["classes"]) || array_key_exists("classes", $context) ? $context["classes"] : (function () { throw new Twig_Error_Runtime('Variable "classes" does not exist.', 12, $this->source); })()));
        foreach ($context['_seq'] as $context["_key"] => $context["class"]) {
            // line 13
            echo "            ";
            if (twig_get_attribute($this->env, $this->source, $context["class"], "trait", array())) {
                // line 14
                echo "                <div class=\"row\">
                    <div class=\"col-md-6\">
                        ";
                // line 16
                echo $context["__internal_060648be7509ffdc1cd8971040d81681617caedbed26e93d4857f5f46e9abcc4"]->macro_class_link($context["class"], true);
                echo "
                    </div>
                    <div class=\"col-md-6\">
                        ";
                // line 19
                echo $this->extensions['Sami\Renderer\TwigExtension']->parseDesc($context, twig_get_attribute($this->env, $this->source, $context["class"], "shortdesc", array()), $context["class"]);
                echo "
                    </div>
                </div>
            ";
            }
            // line 23
            echo "        ";
        }
        $_parent = $context['_parent'];
        unset($context['_seq'], $context['_iterated'], $context['_key'], $context['class'], $context['_parent'], $context['loop']);
        $context = array_intersect_key($context, $_parent) + $_parent;
        // line 24
        echo "    </div>
";
    }

    public function getTemplateName()
    {
        return "traits.twig";
    }

    public function isTraitable()
    {
        return false;
    }

    public function getDebugInfo()
    {
        return array (  90 => 24,  84 => 23,  77 => 19,  71 => 16,  67 => 14,  64 => 13,  60 => 12,  53 => 7,  50 => 6,  44 => 4,  37 => 3,  33 => 1,  31 => 2,  15 => 1,);
    }

    public function getSourceContext()
    {
        return new Twig_Source("{% extends \"layout/layout.twig\" %}
{% from \"macros.twig\" import class_link %}
{% block title %}Traits | {{ parent() }}{% endblock %}
{% block body_class 'traits' %}

{% block page_content %}
    <div class=\"page-header\">
        <h1>Traits</h1>
    </div>

    <div class=\"container-fluid underlined\">
        {% for class in classes %}
            {% if class.trait %}
                <div class=\"row\">
                    <div class=\"col-md-6\">
                        {{ class_link(class, true) }}
                    </div>
                    <div class=\"col-md-6\">
                        {{ class.shortdesc|desc(class) }}
                    </div>
                </div>
            {% endif %}
        {% endfor %}
    </div>
{% endblock %}
", "traits.twig", "phar://C:/sami/sami.phar/Sami/Resources/themes\\default/traits.twig");
    }
}
