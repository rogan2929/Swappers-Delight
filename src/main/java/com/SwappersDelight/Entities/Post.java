/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
package com.SwappersDelight.Entities;

import java.util.ArrayList;

/**
 * Base Post Graph Object
 * 
 * @author rogan2929
 */
public class Post extends GraphObject {

    protected String message;
    protected User actor;
    protected int commentCount;
    protected int likeCount;
    protected boolean userLikes;
    protected ArrayList<ImageObject> imageData;
    protected LinkData linkData;
    protected PostType type;

    public Post() {
    }

    public Post(String id) {
        super(id);
        
        this.imageData = new ArrayList<>();
    }

    public Post(String message, User actor, int commentCount, int likeCount, boolean userLikes) {
        this.message = message;
        this.actor = actor;
        this.commentCount = commentCount;
        this.likeCount = likeCount;
        this.userLikes = userLikes;
        
        this.imageData = new ArrayList<>();
    }

    public String getMessage() {
        return message;
    }

    public void setMessage(String message) {
        this.message = message;
    }

    public User getActor() {
        return actor;
    }

    public void setActor(User actor) {
        this.actor = actor;
    }

    public int getCommentCount() {
        return commentCount;
    }

    public void setCommentCount(int commentCount) {
        this.commentCount = commentCount;
    }

    public int getLikeCount() {
        return likeCount;
    }

    public void setLikeCount(int likeCount) {
        this.likeCount = likeCount;
    }

    public boolean isUserLikes() {
        return userLikes;
    }

    public void setUserLikes(boolean userLikes) {
        this.userLikes = userLikes;
    }
}
