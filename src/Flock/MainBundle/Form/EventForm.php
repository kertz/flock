<?php
/**
 * Created by amalraghav <amal.raghav@gmail.com>
 * Date: 17/04/11
 */
 
namespace Flock\MainBundle\Form;

use Symfony\Component\Form\FormBuilder;
use Symfony\Component\Form\Type\AbstractType;

class EventForm extends AbstractType
{
    public function buildForm(FormBuilder $builder, array $options)
    {
        $builder
            ->add('event_name')
            ->add('event_details')
            ->end();
    }

    public function getDefaultOptions(array $options)
    {
        return array(
            'data_class' => 'Flock\MainBundle\Entity\Event',
        );
    }

    public function getName()
    {
        return 'eventform';
    }
}