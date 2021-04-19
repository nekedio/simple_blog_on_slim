<?php

namespace App;

class PostRepository
{
    public function all()
    {
        $postsJson = explode("\n", trim(file_get_contents('posts.txt')));
        $posts = array_map(function ($post) {
            return json_decode($post, true);
        }, $postsJson);
        return $posts;
    }
    
    public function save($newPost)
    {
        $posts = self::all();
        if ($newPost['id'] == null) {
            $newPost['id'] = uniqid();
            array_unshift($posts, $newPost);
            $newPosts = $posts;
        } else {
            $newPosts = array_map(function ($post) use ($newPost) {
                if ($post['id'] === $newPost['id']) {
                    $post['name'] = $newPost['name'];
                    $post['body'] = $newPost['body'];
                }
                return $post;
            }, $posts);
        }
        $postsJson = implode("\n", array_map(fn($post) => (json_encode($post)), $newPosts));
        return file_put_contents('posts.txt', $postsJson);
    }
    
    public function find($id)
    {
        $users = self::all();
        $user = collect($users)->firstWhere('id', $id);
        return $user;
    }

    public function destroy($id)
    {
        $posts = self::all();
        $filtered = array_filter($posts, fn($post)=>($post['id'] != $id));
        $postsJson = implode("\n", array_map(fn($post) => (json_encode($post)), $filtered));
        return file_put_contents('posts.txt', $postsJson);
    }

}

