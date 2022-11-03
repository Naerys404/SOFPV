<?php

namespace App\Form;

use App\Entity\Article;
use App\Form\VideoType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;

class ArticleType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title', TextType::class , [
                'label'=>'Titre de l\'article',
                'required'=>true
            ])
            ->add('content', TextareaType::class, [
                'label'=> 'Contenu de l\'article',
                'attr'=> ['placeholder'=>'Ajoutez un texte à votre article', 'rows'=>7, 'cols'=>7 ]
            ])
            ->add('video', VideoType::class);
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Article::class,
        ]);
    }
}