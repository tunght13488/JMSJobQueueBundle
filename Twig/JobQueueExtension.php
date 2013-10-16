<?php

namespace JMS\JobQueueBundle\Twig;

use Symfony\Component\DependencyInjection\Container;

class JobQueueExtension extends \Twig_Extension
{
    const FORMAT = 'Y-m-d H:i:s';
    private $linkGenerators = array();

    private $container;

    public function __construct(Container $container, array $generators = array())
    {
        $this->container      = $container;
        $this->linkGenerators = $generators;
    }

    public function getTests()
    {
        return array(
            'jms_job_queue_linkable' => new \Twig_Test_Method($this, 'isLinkable'),
        );
    }

    public function getFunctions()
    {
        return array(
            'jms_job_queue_path' => new \Twig_Function_Method($this, 'generatePath', array('is_safe' => array('html' => true))),
            'change_time_zone'   => new \Twig_Function_Method($this, 'changeTimeZone', array('is_safe' => array('html' => true))),
        );
    }

    public function getFilters()
    {
        return array(
            'jms_job_queue_linkname' => new \Twig_Filter_Method($this, 'getLinkname'),
            'jms_job_queue_args'     => new \Twig_Filter_Method($this, 'formatArgs'),
        );
    }

    public function formatArgs(array $args, $maxLength = 60)
    {
        $str   = '';
        $first = true;
        foreach ($args as $arg) {
            $argLength = strlen($arg);

            if (!$first) {
                $str .= ' ';
            }
            $first = false;

            if (strlen($str) + $argLength > $maxLength) {
                $str .= substr($arg, 0, $maxLength - strlen($str) - 4) . '...';
                break;
            }

            $str .= $arg;
        }

        return $str;
    }

    public function isLinkable($entity)
    {
        foreach ($this->linkGenerators as $generator) {
            if ($generator->supports($entity)) {
                return true;
            }
        }

        return false;
    }

    public function generatePath($entity)
    {
        foreach ($this->linkGenerators as $generator) {
            if ($generator->supports($entity)) {
                return $generator->generate($entity);
            }
        }

        throw new \RuntimeException(sprintf('The entity "%s" has no link generator.', get_class($entity)));
    }

    /**
     * @param \DateTime $dateTime
     * @return mixed
     */
    public function changeTimeZone($dateTime)
    {
        if (null !== $dateTime) {
            try {
                $defaultTimeZone = $this->container->getParameter('default_time_zone');
            } catch (\Exception $e) {
                $defaultTimeZone = null;
            }

            $timeZoneList = timezone_identifiers_list();
            if (null !== $defaultTimeZone && in_array($defaultTimeZone, $timeZoneList)) {
                $dateTime->setTimezone(new \DateTimeZone($defaultTimeZone));
            }

            $timeString = $dateTime->format(self::FORMAT);
        } else {
            $timeString = '';
        }

        return '<time class="timeago" datetime="' . $timeString . '">' . $timeString . '</time>';
    }

    public function getLinkname($entity)
    {
        foreach ($this->linkGenerators as $generator) {
            if ($generator->supports($entity)) {
                return $generator->getLinkname($entity);
            }
        }

        throw new \RuntimeException(sprintf('The entity "%s" has no link generator.', get_class($entity)));
    }

    public function getName()
    {
        return 'jms_job_queue';
    }
}