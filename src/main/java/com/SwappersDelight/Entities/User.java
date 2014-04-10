/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

package com.SwappersDelight.Entities;

/**
 *
 * @author rogan2929
 */
public class User extends GraphObject {
    private String lastName;
    private String firstName;
    private String profileUrl;
    private ImageInfo image;

    public User() {
    }

    public User(String id, String lastName, String firstName, String profileUrl, ImageInfo image) {
        this.id = id;
        this.lastName = lastName;
        this.firstName = firstName;
        this.profileUrl = profileUrl;
        this.image = image;
    }

    public String getLastName() {
        return lastName;
    }

    public void setLastName(String lastName) {
        this.lastName = lastName;
    }

    public String getFirstName() {
        return firstName;
    }

    public void setFirstName(String firstName) {
        this.firstName = firstName;
    }

    public String getProfileUrl() {
        return profileUrl;
    }

    public void setProfileUrl(String profileUrl) {
        this.profileUrl = profileUrl;
    }

    public ImageInfo getImage() {
        return image;
    }

    public void setImage(ImageInfo image) {
        this.image = image;
    }
}
