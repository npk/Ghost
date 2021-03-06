<?php
namespace Ghost\PostBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

/**
 * Topic controller
 *
 * @author Wenming Tang <tang@babyfamily.com>
 */
class TopicController extends Controller
{
    /**
     * Lists all Topic
     */
    public function categoryAction($categoryAlias)
    {
        $category = $this->get('ghost.manager.category.default')->findCategoryByAlias($categoryAlias);

        if (!$category) {
            throw $this->createNotFoundException();
        }

        $this->get('ghost.breadcrumb')
            ->add($category->getName(), $this->generateUrl('topic_by_category', array('categoryAlias' => $category->getAlias())))
            ->add('Topics');

        $pager = $this->get('ghost.manager.topic.acl')->findTopicsByCategory($category, $this->getRequest()->get('page', 1));

        return $this->render('GhostPostBundle:Topic:category.html.twig', array(
            'pager'    => $pager,
            'category' => $category
        ));
    }

    /**
     * Finds and displays a Topic
     */
    public function showAction($id)
    {
        $topic = $this->get('ghost.manager.topic.acl')->findTopic($id);

        if (!$topic) {
            throw $this->createNotFoundException('Unable to find Topic.');
        }

        $this->get('ghost.breadcrumb')
            ->add($topic->getCategory()->getName(), $this->generateUrl('topic_by_category', array('categoryAlias' => $topic->getCategory()->getAlias())))
            ->add($topic->getTitle());

        $this->get('ghost.manager.topic.default')->incrementViewsCount($topic);

        $posts = $this->get('ghost.manager.post.acl')->findPostsByTopic($topic);

        if ($this->get('ghost.acl.topic')->canReply($topic) && $this->get('ghost.acl.post')->canCreate()) {
            $postForm = $this->get('ghost.form.factory.post_new')->createForm($topic);
        } else {
            $postForm = null;
        }

        return $this->render('GhostPostBundle:Topic:show.html.twig', array(
            'topic'     => $topic,
            'posts'     => $posts,
            'post_form' => (null != $postForm ? $postForm->createView() : null)
        ));
    }

    /**
     * Creates a new Topic
     */
    public function newAction($categoryAlias)
    {
        $category = $this->get('ghost.manager.category.default')->findCategoryByAlias($categoryAlias);

        if (!$category) {
            throw $this->createNotFoundException();
        }

        $this->get('ghost.breadcrumb')
            ->add($category->getName(), $this->generateUrl('topic_by_category', array('categoryAlias' => $category->getAlias())))
            ->add('Post a New Topic');

        $form        = $this->get('ghost.form.factory.topic_new')->createForm($category);
        $formHandler = $this->get('ghost.form.handler.topic');

        if ($topic = $formHandler->process($form)) {
            return $this->redirect($this->generateUrl('topic_show', array('id' => $topic->getId())));
        }

        return $this->render('GhostPostBundle:Topic:new.html.twig', array(
            'category' => $category,
            'form'     => $form->createView()
        ));
    }

    /**
     * Edit an existing Topic
     */
    public function editAction($id)
    {
        $topic = $this->get('ghost.manager.topic.acl')->findTopic($id);

        if (!$topic) {
            throw $this->createNotFoundException('Unable to find Topic.');
        }

        if (!$this->get('ghost.acl.topic')->canEdit($topic)) {
            throw new AccessDeniedException();
        }

        $this->get('ghost.breadcrumb')
            ->add($topic->getCategory()->getName(), $this->generateUrl('topic_by_category', array('categoryAlias' => $topic->getCategory()->getAlias())))
            ->add($topic->getTitle(), $this->generateUrl('topic_show', array('id' => $topic->getId())))
            ->add('Edit the Topic');

        $form        = $this->get('ghost.form.factory.topic_edit')->createForm($topic);
        $formHandler = $this->get('ghost.form.handler.topic');

        if ($formHandler->process($form)) {
            return $this->redirect($this->generateUrl('topic_show', array('id' => $topic->getId())));
        }

        return $this->render('GhostPostBundle:Topic:edit.html.twig', array(
            'topic' => $topic,
            'form'  => $form->createView()
        ));
    }
}
